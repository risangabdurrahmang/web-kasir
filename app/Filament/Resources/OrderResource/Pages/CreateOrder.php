<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // Get the newly created order with eager loading
        $order = $this->record->load('items');

        DB::transaction(function () use ($order) {
            // Loop through each order item
            foreach ($order->items as $item) {
                // Get the product associated with the order item
                $product = $item->product;

                // Decrease the stock of the product
                $product->stock -= $item->quantity;

                // Save the updated product
                $product->save();
            }
        });
    }
}
