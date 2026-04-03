<?php
use App\Http\Controllers\HdfcController;
use Illuminate\Support\Facades\Route;

Route::post('/hdfc/payment/callback', [HdfcController::class, 'getPaymentStatus'])
    ->name('hdfc.payment.callback');

