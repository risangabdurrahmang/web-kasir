<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Models\Product;

class OrderItemObserver
{
    // decrease stock product when order created
    public function creating(OrderItem $orderItem)
    {
        $product = Product::find($orderItem->product_id);
        if ($product->stock < $orderItem->quantity) {
            throw new \Exception('Insufficient stock.');
        }
        $product->stock -= $orderItem->quantity;
        $product->save();
    }

    // decrease stock product when quantity add and increase stock product when quantity reduce
    public function updating(OrderItem $orderItem)
    {
        $oldQuantity = $orderItem->getOriginal('quantity');
        $newQuantity = $orderItem->quantity;
        $product = Product::find($orderItem->product_id);

        if ($newQuantity > $oldQuantity) {
            $difference = $newQuantity - $oldQuantity;
            if ($product->stock < $difference) {
                throw new \Exception('Insufficient stock.');
            }
            $product->stock -= $difference;
        } elseif ($newQuantity < $oldQuantity) {
            $difference = $oldQuantity - $newQuantity;
            $product->stock += $difference;
        }

        $product->save();
    }
}
