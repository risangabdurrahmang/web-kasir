<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Str;

class PosPage extends Component implements HasForms
{
    use InteractsWithForms;
    use WithPagination;

    public $customer_id;
    public $payment_id;
    public $payments = '';
    public $customers = [];
    public $cartItems = [];
    public $search = '';
    public $paid;
    public $change;
    public $showCashModal = false;

    // take data customer from database
    public function loadCustomers()
    {
        $this->customers = Customer::all()->toArray();
    }

    // take data payment from database
    public function loadPayments()
    {
        $this->payments = Payment::all();
    }

    // take data product from database
    public function loadProducts()
    {
        $query = Product::with('category')
            ->where('stock', '>', 0)
            ->where('is_active', true);

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return $query->paginate(6);
    }

    public function checkoutForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('payment_id')
                    ->relationship('payment', 'name')
                    ->options(function () {
                        return Payment::where('is_active', true)->pluck('name', 'id');
                    })
                    ->native(false)
                    ->required(),
            ])
            ->model(Order::class);
    }

    public function confirmForm(Form $form): Form
    {
        return $form->schema([
            TextInput::make('paid')
                ->numeric()
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn($state) => $this->updatedPaid($state)),
            TextInput::make('change')
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

    // add product to cart
    public function addItem($productId)
    {
        $product = Product::find($productId);
        if ($product) {
            $key = $product->id;
            if (isset($this->cartItems[$key])) {
                if ($this->cartItems[$key]['quantity'] + 1 > $product->stock) {
                    Notification::make()
                        ->title('Error')
                        ->body('Order quantity exceeds available stock')
                        ->danger()
                        ->send();
                    return;
                }
                $this->cartItems[$key]['quantity'] += 1;
            } else {
                $this->cartItems[$key] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'quantity' => 1,
                    'image' => $product->image,
                    'stock' => $product->stock,
                ];
            }
        }
    }

    // remove product from cart
    public function removeItem($key)
    {
        if (isset($this->cartItems[$key])) {
            if ($this->cartItems[$key]['quantity'] > 1) {
                $this->cartItems[$key]['quantity'] -= 1;
            } else {
                unset($this->cartItems[$key]);
            }
        }
    }

    // count total price
    public function getTotalPrice()
    {
        return array_reduce($this->cartItems, function ($total, $item) {
            return $total + ($item['price'] * $item['quantity']);
        }, 0);
    }

    // update field money changes when paid input
    public function updatedPaid($value)
    {
        $this->change = $this->calculateChange($value);
    }

    // count change
    public function calculateChange($paid)
    {
        $totalPrice = $this->getTotalPrice();
        $paidAmount = (float) $paid;
        $change = $paidAmount - $totalPrice;
        return $change;
    }

    public function checkout()
    {
        $payment = Payment::find($this->payment_id);
        $customer = Customer::find($this->customer_id);

        if (empty($this->cartItems)) {
            Notification::make()
                ->title('Cart is empty')
                ->danger()
                ->send();
            return;
        }

        if (!$customer) {
            Notification::make()
                ->title('Please select customer')
                ->danger()
                ->send();
            return;
        }

        if (!$payment) {
            Notification::make()
                ->title('Please select payment method')
                ->danger()
                ->send();
            return;
        }

        if ($payment->name === 'Cash') {
            $this->showCashModal = true;
        } else {
            $this->processOnlinePayment();
        }
    }

    public function processOnlinePayment()
    {
        $itemDetails = [];

        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        $order = Order::create([
            'order_number' => 'OLN-' . Str::random(10),
            'customer_id' => $this->customer_id,
            'payment_id' => $this->payment_id,
            'total' => $this->getTotalPrice(),
            'paid' => 0,
            'change' => 0,
            'status' => 'pending'
        ]);

        foreach ($this->cartItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'sub_total' => $item['price'] * $item['quantity'],
            ]);
            $itemDetails[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => (float) $item['price'],
                'quantity' => (int) $item['quantity'],
            ];
        }

        $grossAmount = array_reduce($itemDetails, function ($total, $item) {
            return $total + ($item['price'] * $item['quantity']);
        }, 0);

        $params = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $order->customer->name,
                'email' => $order->customer->email,
                'phone' => $order->customer->phone,
            ],
            'item_details' => $itemDetails,
        ];

        try {
            $snapToken = Snap::getSnapToken($params);

            $this->dispatch('snapPayment', snapToken: $snapToken);
            $this->reset();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Payment Gateway Error')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function processCashPayment()
    {
        $this->validate([
            'paid' => 'required|numeric|min:' . $this->getTotalPrice(),
        ], [
            'paid.min' => 'Paid amount must be at least the total price'
        ]);

        try {
            DB::transaction(function () {
                $order = Order::create([
                    'order_number' => 'CSH' . Str::random(10),
                    'customer_id' => $this->customer_id,
                    'payment_id' => $this->payment_id,
                    'paid' => $this->paid,
                    'change' => $this->paid - $this->getTotalPrice(),
                    'total' => $this->getTotalPrice(),
                    'status' => 'paid',
                ]);

                foreach ($this->cartItems as $item) {
                    $product = Product::find($item['id']);
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'sub_total' => $product->price * $item['quantity'],
                    ]);
                }
            });

            $this->reset();

            Notification::make()
                ->title('Order completed successfully')
                ->success()
                ->send();

            return redirect('/orders');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
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
