<?php

use App\Http\Controllers\MidtransWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/midtrans', MidtransWebhookController::class)
    ->middleware('throttle:120,1,midtrans-webhook')
    ->name('webhooks.midtrans');
