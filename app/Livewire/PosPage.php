<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class PosPage extends Component
{
    use WithPagination;

    public $customer_id;
    public $payment_id;
    public $payments = '';
    public $customers = [];
    public $cartItems = [];
    public $search = '';
    public $paid;
    public $money_changes;
    public $showModal = false;

    public function mount()
    {
        $this->loadPayments();
        $this->loadCustomers();
        $this->loadProducts();
    }

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
        return Product::where('stock', '>', 0)
            ->where('is_active', true)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->paginate(6);
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
        $this->money_changes = $this->calculateMoneyChanges($value);
    }

    // count money changes
    public function calculateMoneyChanges($paid)
    {
        $totalPrice = $this->getTotalPrice();
        $paidAmount = (float) $paid;
        $money_changes = $paidAmount - $totalPrice;
        return $money_changes;
    }

    // save order
    public function saveOrder()
    {
        $payment = Payment::find($this->payment_id);

        $rules = [
            'customer_id' => 'required',
            'payment_id' => 'required',
        ];

        $messages = [
            'customer_id.required' => 'Customer field is required',
            'payment_id.required' => 'Payment field is required',
        ];

        if ($payment && $payment->name === 'Cash') {
            $rules['paid'] = 'required|numeric|min:' . $this->getTotalPrice();
            $messages['paid.required'] = 'Paid field is required';
            $messages['paid.min'] = 'Paid payment must be at least the total price';
        }

        try {
            $this->validate($rules, $messages);

            if ($payment->name !== 'Cash') {
                $this->paid = 0;
                $this->money_changes = 0;
            }

            DB::transaction(function () {
                $order = Order::create([
                    'customer_id' => $this->customer_id,
                    'payment_id' => $this->payment_id,
                    'paid' => $this->paid,
                    'money_changes' => $this->money_changes,
                    'total' => $this->getTotalPrice(),
                ]);

                foreach ($this->cartItems as $cartItem) {
                    $product = Product::find($cartItem['id']);

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $cartItem['quantity'],
                        'sub_total' => $product->price * $cartItem['quantity'],
                    ]);
                }
            });

            Notification::make()
                ->title('Order Saved Successfully')
                ->success()
                ->send();

            $this->reset();
            return redirect('/admin/orders');
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Error Validation')
                ->danger()
                ->body($e->getMessage())
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    // show popup modal when payment cash selected and contain paid dan money changes field
    public function cashPopup()
    {
        if (empty($this->cartItems)) {
            Notification::make()
                ->title('Cart is empty')
                ->danger()
                ->send();
            return;
        }

        try {
            $this->validate([
                'customer_id' => 'required',
                'payment_id' => 'required',
            ], [
                'customer_id.required' => 'Customer field is required',
                'payment_id.required' => 'Payment field is required',
            ]);

            $payment = Payment::find($this->payment_id);
            if ($payment && $payment->name === 'Cash') {
                $this->showModal = true;
            } else {
                $this->saveOrder();
            }
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Error Validation')
                ->danger()
                ->body($e->getMessage())
                ->send();
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
