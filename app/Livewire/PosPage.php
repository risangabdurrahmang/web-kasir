<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class PosPage extends Component implements HasForms
{
    use InteractsWithForms;
    use WithPagination;

    public $customer_id;
    public $payment_id;
    public $payments;
    public $customers;
    public $cartItems = [];
    public $search = '';
    public $paid = 0;
    public $total = 0;
    public $money_changes = 0;
    public $showModal = false;

    public function mount()
    {
        $this->loadPayments();
        $this->loadCustomers();
        $this->calculateTotal();
    }

    public function loadCustomers()
    {
        $this->customers = Customer::all();
    }

    public function loadPayments()
    {
        $this->payments = Payment::all();
    }

    public function loadProducts()
    {
        return Product::where('stock', '>', 0)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->paginate(6);
    }

    public function checkoutForm(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Select::make('customer_id')
                    ->searchable()
                    ->relationship('customer', 'name')
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter name')
                            ->columns(1),
                        Select::make('gender')
                            ->placeholder('Select gender')
                            ->options([
                                'Laki-laki' => 'Laki-laki',
                                'Perempuan' => 'Perempuan',
                            ])
                            ->required()
                            ->native(false)
                            ->columns(1),
                        TextInput::make('email')
                            ->email()
                            ->unique()
                            ->required()
                            ->placeholder('Enter email')
                            ->columns(1),
                        TextInput::make('phone')
                            ->required()
                            ->placeholder('Enter phone')
                            ->columns(1),
                    ])->columns(2)
                    ->createOptionAction(function (Action $action) {
                        return $action
                            ->modalHeading('Create customer')
                            ->modalSubmitActionLabel('Create customer')
                            ->modalWidth('lg');
                    }),
                Select::make('payment_id')
                    ->relationship('payment', 'payment_method')
                    ->native(false)
                    ->required()
                    ->options(function () {
                        return Payment::where('is_visible', true)->pluck('payment_method', 'id');
                    }),
            ]),
        ])->model(Order::class);
    }

    public function confirmForm(Form $form): Form
    {
        return $form->schema([
            TextInput::make('total')
                ->numeric()
                ->disabled()
                ->default($this->total),
            TextInput::make('paid')
                ->numeric()
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn($state) => $this->updatedPaid($state)),
            TextInput::make('money_changes')
                ->numeric()
                ->disabled(),
        ])->model(Order::class);
    }

    protected function getForms(): array
    {
        return [
            'checkoutForm',
            'confirmForm',
        ];
    }

    public function addItem($productId)
    {
        $product = Product::find($productId);

        if ($product && $product->stock > 0) {
            $key = $product->id;

            if (isset($this->cartItems[$key])) {
                $this->cartItems[$key]['quantity'] += 1;
            } else {
                $this->cartItems[$key] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->image,
                    'price' => $product->price,
                    'quantity' => 1,
                    'stock' => $product->stock,
                ];
            }
        }
        $this->calculateTotal();
    }

    public function removeItem($key)
    {
        if (isset($this->cartItems[$key])) {
            unset($this->cartItems[$key]);
        }
        $this->calculateTotal();
    }

    public function checkoutValidate()
    {
        if (empty($this->cartItems)) {
            Notification::make()
                ->title('Keranjang belanja kosong')
                ->danger()
                ->send();
            return;
        }

        try {
            $this->validate([
                'customer_id' => 'required',
                'payment_id' => 'required',
            ], [
                'customer_id.required' => 'Customer required',
                'payment_id.required' => 'Payment required',
            ]);

            $this->showModal = true;
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function calculateTotal()
    {
        return $this->total = array_reduce($this->cartItems, function ($total, $item) {
            return $total + ($item['price'] * $item['quantity']);
        }, 0);
    }

    public function updatedPaid($value)
    {
        $this->money_changes = $this->calculateMoneyChanges($value);
    }

    public function calculateMoneyChanges($paid)
    {
        $totalPrice = $this->calculateTotal();
        if ($paid < $totalPrice) {
            $this->addError('paid', 'Jumlah pembayaran tidak bisa kurang dari total harga.');
            return null;
        }
        $money_changes = $paid - $totalPrice;
        return $money_changes;
    }

    public function saveOrder()
    {
        if (empty($this->cartItems)) {
            Notification::make()
                ->title('Empty Cart')
                ->danger()
                ->send();
            return;
        }

        $payment = Payment::find($this->payment_id);

        $rules = ['customer_id' => 'required'];
        $messages = ['customer_id.required' => 'Customer required'];

        if ($payment->payment_method !== 'QRIS') {
            $rules['paid'] = 'required';
            $messages['paid.required'] = 'Payment required';
        }

        try {
            $this->validate($rules, $messages);

            DB::transaction(function () use ($payment) {
                $order = Order::create([
                    'customer_id' => $this->customer_id,
                    'payment_id' => $this->payment_id,
                    'paid' => $payment->payment_method === 'QRIS' ? 0 : $this->paid,
                    'money_changes' => $this->money_changes,
                    'total' => $this->total,
                ]);

                foreach ($this->cartItems as $cartItem) {
                    $product = Product::find($cartItem['id']);
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $cartItem['quantity'],
                        'sub_total' => $product->price * $cartItem['quantity'],
                    ]);
                    $product->decrement('stock', $cartItem['quantity']);
                }
            });

            Notification::make()
                ->title('Saved Sucessfully')
                ->success()
                ->send();

            $this->reset();
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Error Validation')
                ->danger()
                ->body($e->getMessage())
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to Save')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.pos-page', [
            'products' => $this->loadProducts(),
        ]);
    }
}
