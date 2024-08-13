<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // Get the newly created order with its related items
        $order = $this->record->load('items');

        // Perform the stock reduction inside a database transaction
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                // Get the associated product
                $product = $item->product;

                if ($product) {
                    // Reduce the product stock by the quantity ordered
                    $product->decrement('stock', $item->quantity);

                    // Save the updated product
                    $product->save();
                }
            }
        });
    }
}
