<?php

namespace App\Observers;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function created(Order $order): void
    {
        // Load items with product relation
        $order->load('items.product');

        // Check if items are loaded correctly
        if ($order->items === null || $order->items->isEmpty()) {
            Log::error('Order items are empty or null', ['order_id' => $order->id]);
            return;
        }

        foreach ($order->items as $orderItem) {
            $product = $orderItem->product;
            if ($product) {
                Log::info('Mengurangi stok produk', ['product_id' => $product->id, 'quantity' => $orderItem->quantity]);
                $product->decrement('stock', $orderItem->quantity);
                $product->save();
            } else {
                Log::warning('Produk tidak ditemukan', ['order_item_id' => $orderItem->id]);
            }
        }
    }

    public function updated(Order $order): void
    {
        $order->load('items.product');

        if ($order->items === null || $order->items->isEmpty()) {
            Log::error('Order items are empty or null', ['order_id' => $order->id]);
            return;
        }

        foreach ($order->items as $item) {
            $product = $item->product;
            if ($product) {
                $previousQuantity = $item->getOriginal('quantity');

                if ($previousQuantity === null) {
                    // This is a new item, so decrement the stock
                    Log::info('Mengurangi stok produk', ['product_id' => $product->id, 'quantity' => $item->quantity]);
                    $product->stock -= $item->quantity;
                } elseif ($item->quantity > $previousQuantity) {
                    // The quantity has increased, so decrement the stock
                    Log::info('Mengurangi stok produk', ['product_id' => $product->id, 'quantity' => $item->quantity - $previousQuantity]);
                    $product->stock -= $item->quantity - $previousQuantity;
                } elseif ($item->quantity < $previousQuantity) {
                    // The quantity has decreased, so increment the stock
                    Log::info('Menambah stok produk', ['product_id' => $product->id, 'quantity' => $previousQuantity - $item->quantity]);
                    $product->stock += $previousQuantity - $item->quantity;
                }

                $product->save();
            } else {
                Log::warning('Produk tidak ditemukan', ['order_item_id' => $item->id]);
            }
        }
    }

    public function deleted(Order $order): void
    {
        // Load items with product relation
        $order->load('items.product');

        // Check if items are loaded correctly
        if ($order->items === null || $order->items->isEmpty()) {
            Log::error('Order items are empty or null', ['order_id' => $order->id]);
            return;
        }

        foreach ($order->items as $orderItem) {
            $product = $orderItem->product;
            if ($product) {
                Log::info('Menambah stok produk', ['product_id' => $product->id, 'quantity' => $orderItem->quantity]);
                $product->increment('stock', $orderItem->quantity);
                $product->save();
            } else {
                Log::warning('Produk tidak ditemukan', ['order_item_id' => $orderItem->id]);
            }
        }
    }
}
