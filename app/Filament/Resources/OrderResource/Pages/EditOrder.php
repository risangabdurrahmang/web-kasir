<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Product;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // hook yang dijalankan ketika form sudah di edit untuk mengurangi stok produk ketika jumlah di tambah dan sebaliknya
    protected function afterSave(): void
    {
        DB::transaction(function () {
            $order = Order::find($this->record->id);
            $orderItems = $order->orderItems;

            if ($orderItems) {
                foreach ($orderItems as $orderItem) {
                    $product = Product::find($orderItem->product_id);
                    $previousQuantity = $orderItem->getOriginal('quantity');
                    $currentQuantity = $orderItem->quantity;

                    if ($currentQuantity > $previousQuantity) {
                        $product->stock -= $currentQuantity - $previousQuantity;
                    } elseif ($currentQuantity < $previousQuantity) {
                        $product->stock += $previousQuantity - $currentQuantity;
                    }

                    $product->save();
                }
            }
        });
    }
}
