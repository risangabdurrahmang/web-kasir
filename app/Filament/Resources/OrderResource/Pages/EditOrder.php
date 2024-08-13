<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Actions;
use Filament\Notifications\Notification;
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

    protected function afterSave(): void
    {
        $order = $this->record->load('items.product');  // Get the current order record

        DB::transaction(function () use ($order) {
            // $order->load('items.product');  // Load the related items and products

            foreach ($order->items as $item) {
                $product = $item->product;

                // Load the original quantity from the database
                $originalItem = OrderItem::find($item->id);
                $prev_quantity = $originalItem->quantity;
                $new_quantity = $item->quantity;

                if ($new_quantity > $prev_quantity) {
                    // If the new quantity is greater, decrease the stock
                    $difference = $new_quantity - $prev_quantity;
                    $product->decrement('stock', $difference);
                    $product->save();
                } elseif ($new_quantity < $prev_quantity) {
                    // If the new quantity is less, increase the stock
                    $difference = $prev_quantity - $new_quantity;
                    $product->increment('stock', $difference);
                    $product->save();
                }

                Log::info("Updated product stock: {$product->stock}");
            }
        });
    }
}
