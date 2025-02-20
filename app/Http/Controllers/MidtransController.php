<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Midtrans\Config;
use Midtrans\Notification;

class MidtransController extends Controller
{
    public function notification()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');

        $notification = new Notification();

        $order = Order::where('order_number', $notification->order_id)->first();

        $transaction_status = $notification->transaction_status;
        $fraud = $notification->fraud_status;

        if ($transaction_status == 'capture') {
            if ($fraud == 'challenge') {
                $order->status = 'pending';
            } else if ($fraud == 'accept') {
                $order->status = 'paid';
                $order->paid = $notification->gross_amount;
            }
        } else if ($transaction_status == 'settlement') {
            $order->status = 'paid';
            $order->paid = $notification->gross_amount;
        } else if ($transaction_status == 'cancel' || $transaction_status == 'deny' || $transaction_status == 'expire') {
            $order->status = 'cancelled';
        } else if ($transaction_status == 'pending') {
            $order->status = 'pending';
        }

        $order->save();

        return response()->json(['status' => 'success']);
    }
}
