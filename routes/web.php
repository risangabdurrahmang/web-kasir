<?php

use App\Http\Controllers\MidtransController;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('midtrans/notification', [MidtransController::class, 'notification']);
