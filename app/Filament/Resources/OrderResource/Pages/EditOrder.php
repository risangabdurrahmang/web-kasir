<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Product;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    protected function afterUpdate(): void
    {
        // Get the order data
        $order = $this->record;

        DB::transaction(function () use ($order) {
            // Load items with product relation
            $order->load('items.product');
            Log::info('Loaded order items with products', ['order_id' => $order->id, 'items' => $order->items]);

            // Loop through each order item
            foreach ($order->items as $item) {
                // Get the product associated with the order item
                $product = $item->product;

                if ($product) {
                    // Get the previous quantity of the order item
                    $previousQuantity = $item->getOriginal('quantity');
                    Log::info('Processing item', ['item_id' => $item->id, 'previous_quantity' => $previousQuantity, 'new_quantity' => $item->quantity]);

                    // Decrease or increase the stock of the product based on quantity change
                    if ($item->quantity > $previousQuantity) {
                        $difference = $item->quantity - $previousQuantity;
                        $product->decrement('stock', $difference);
                        Log::info('Decreasing stock', ['product_id' => $product->id, 'decrement' => $difference]);
                    } elseif ($item->quantity < $previousQuantity) {
                        $difference = $previousQuantity - $item->quantity;
                        $product->increment('stock', $difference);
                        Log::info('Increasing stock', ['product_id' => $product->id, 'increment' => $difference]);
                    }

                    // Save the updated product
                    $product->save();
                } else {
                    Log::warning('Product not found for order item', ['order_item_id' => $item->id]);
                }
            }
        });
    }
}
