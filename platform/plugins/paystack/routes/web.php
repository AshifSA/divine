<?php

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Botble\Paystack\Http\Controllers'], function (): void {
    Route::get('paystack/payment/callback', [
        'as' => 'paystack.payment.callback',
        'uses' => 'PaystackController@getPaymentStatus',
    ]);
});
