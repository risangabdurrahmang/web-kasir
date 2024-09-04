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

    public function loadCustomers()
    {
        $this->customers = Customer::all()->toArray();
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

    public function addItem($productId)
    {
        $product = Product::find($productId);

        if ($product) {
            $key = $product->id;

            if (isset($this->cartItems[$key])) {
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

    public function getTotalPrice()
    {
        return array_reduce($this->cartItems, function ($total, $item) {
            return $total + ($item['price'] * $item['quantity']);
        }, 0);
    }

    public function updatedPaid($value)
    {
        $this->money_changes = $this->calculateMoneyChanges($value);
    }

    public function calculateMoneyChanges($paid)
    {
        $totalPrice = $this->getTotalPrice();
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
                ->title('Keranjang belanja kosong')
                ->danger()
                ->send();
            return;
        }

        $payment = Payment::find($this->payment_id);

        $rules = [
            'customer_id' => 'required',
        ];

        $messages = [
            'customer_id.required' => 'Pelanggan harus diisi.',
        ];

        if ($payment->payment_method !== 'QRIS') {
            $rules['paid'] = 'required';
            $messages['paid.required'] = 'Nominal pembayaran harus diisi.';
        }

        try {
            $this->validate($rules, $messages);

            if ($payment->payment_method !== 'QRIS' && $this->paid < $this->getTotalPrice()) {
                $this->addError('paid', 'Nominal pembayaran harus minimal sebesar total harga.');
                return;
            }

            DB::transaction(function () use ($payment) {
                $order = Order::create([
                    'customer_id' => $this->customer_id,
                    'payment_id' => $this->payment_id,
                    'paid' => $payment->payment_method === 'QRIS' ? 0 : $this->paid,
                    'money_changes' => $payment->payment_method === 'QRIS' ? 0 : $this->calculateMoneyChanges($this->paid),
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

                    $product->decrement('stock', $cartItem['quantity']);
                }
            });

            Notification::make()
                ->title('Pesanan berhasil dibuat')
                ->success()
                ->send();

            $this->reset();
            return redirect('/admin/orders');
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Terjadi kesalahan validasi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Terjadi kesalahan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function confirmPayment()
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
                'payment_id' => 'required',
                'customer_id' => 'required',
            ], [
                'customer_id.required' => 'Pelanggan harus diisi.',
                'payment_id.required' => 'Metode pembayaran harus dipilih.',
            ]);
            $this->showModal = true;
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Terjadi kesalahan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Terjadi kesalahan')
                ->body($e->getMessage())
                ->danger()
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
