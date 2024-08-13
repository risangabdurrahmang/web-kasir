<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PosPage extends Component
{
    public $customer_id = '';
    public $payment_id = '';

    public $categories = '';
    public $products = '';
    public $customers = '';
    public $payments = '';
    public $cartItems = [];
    public $search = '';
    public $paid;
    public $money_changes;

    public function mount()
    {
        $this->loadCategories();
        $this->loadProducts();
        $this->loadCustomers();
        $this->loadPayments();
    }

    public function loadCustomers()
    {

        $this->customers = Customer::all();
    }

    public function loadPayments()
    {
        $this->payments = Payment::all();
    }

    public function loadCategories()
    {
        $this->categories = Category::all();
    }

    public function loadProducts()
    {
        $this->products = Product::where('stock', '>', 0)->when($this->search, function ($query) {
            $query->where('name', 'like', '%' . $this->search . '%');
        })->get();
    }

    public function updatedSearch()
    {
        $this->loadProducts();
    }

    public function addItem(Product $product)
    {
        if (isset($this->cartItems[$product->name])) {
            $item = $this->cartItems[$product->name];
            $this->cartItems[$product->name] = [
                'quantity' => $item['quantity'] + 1,
                'price' => $item['price'] + $product->price,
                'image' => $item['image'],
                'name' => $item['name'],
                'stock' => $product->stock,
            ];
        } else {
            $this->cartItems[$product->name] = [
                'quantity' => 1,
                'price' => $product->price,
                'image' => $product->image,
                'name' => $product->name,
                'stock' => $product->stock
            ];
        }
    }

    public function removeItem($key)
    {
        $item = $this->cartItems[$key];

        if ($item['quantity'] > 1) {
            $hargasatuan = $item['price'] / $item['quantity'];
            $quantitybaru = $item['quantity'] - 1;

            $this->cartItems[$key]['quantity'] = $quantitybaru;
            $this->cartItems[$key]['price'] = $hargasatuan * $quantitybaru;
        } else {
            unset($this->cartItems[$key]);
        }
    }

    public function getTotalPrice()
    {
        if (isset($this->cartItems)) {
            $pricess = array_column($this->cartItems, 'price');
            return array_sum($pricess);
        } else {
            return 0;
        }
    }

    public function updatedPaid($value)
    {
        $this->money_changes = $this->calculateMoneyChanges($value);
    }

    public function calculateMoneyChanges($paid)
    {
        $totalPrice = $this->getTotalPrice();
        if ($paid < $totalPrice) {
            $this->addError('paid', 'Paid amount cannot be less than total price');
            return null;
        }
        $money_changes = $paid - $totalPrice;
        return $money_changes;
    }

    public function saveOrder()
    {
        try {
            // Validasi input
            $this->validate([
                'payment_id' => 'required|exists:payments,id',
                // 'paid' => 'required_if:payment_id,cash|numeric|min:1',
            ]);

            DB::transaction(function () {
                // Buat order baru
                $order = Order::create([
                    'payment_id' => $this->payment_id,
                    'order_date' => now(),
                    // 'paid' => $this->paid,
                    // 'money_changes' => $this->calculateMoneyChanges($this->paid),
                    'total' => $this->getTotalPrice(),
                ]);

                foreach ($this->cartItems as $cartItem) {
                    // Cari produk berdasarkan nama
                    $product = Product::where('name', $cartItem['name'])->first();

                    if ($product->stock < $cartItem['quantity']) {
                        // throw new \Exception("Not enough stock for product '{$product->name}'");
                        Notification::make()
                            ->title('Error')
                            ->body("Not enough stock for product '{$product->name}'")
                            ->danger()
                            ->send();
                    }

                    // Buat item pesanan
                    $orderItem = new OrderItem();
                    $orderItem->order_id = $order->id;
                    $orderItem->product_id = $product->id;
                    $orderItem->quantity = $cartItem['quantity'];
                    $orderItem->sub_total = $product->price * $cartItem['quantity'];
                    $orderItem->save();

                    // Kurangi stok produk
                    $product->decrement('stock', $cartItem['quantity']);
                }
            });
            Notification::make()
                ->title('Saved successfully')
                ->success()
                ->send();
            $this->reset(); // reset the form data
            return redirect('/admin/orders');
        } catch (\Illuminate\Validation\ValidationException $e) {
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

    public function render()
    {
        return view('livewire.pos-page');
    }
}
