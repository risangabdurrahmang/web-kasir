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

    // hook ketika data sudah dibuat maka kurangi stok produk
    protected function afterCreate(): void
    {
        $order = $this->record->load('items');

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {

                $product = $item->product;

                if ($product) {
                    $product->decrement('stock', $item->quantity);

                    $product->save();
                }
            }
        });
    }
}
