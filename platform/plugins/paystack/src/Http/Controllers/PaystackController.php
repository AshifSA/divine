<?php

namespace Botble\Paystack\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Supports\PaymentHelper;
use Botble\Paystack\Services\Paystack;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Botble\Ecommerce\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Botble\Ecommerce\Repositories\Interfaces\OrderInterface;
class PaystackController extends BaseController
{ 
    public function getPaymentStatus(Request $request, BaseHttpResponse $response)
    {
        // -------------------------------------------------
        // 1️⃣ Validate callback
        // -------------------------------------------------
        if ($request->input('gateway') !== 'hdfc' || empty($request->order_id)) {
            return $response
                ->setError()
                ->setMessage('Invalid HDFC callback data');
        }
    
        $orderId = $request->order_id;
    
        // -------------------------------------------------
        // 2️⃣ HDFC Config
        // -------------------------------------------------
        $apiKey     = '64932EB4F574FD39C2CEC25E618144';
        $merchantId = 'SG4271';
        $customerId = 'SG4271';
        $version    = '2023-06-30';
    
        $url = "https://smartgateway.hdfcuat.bank.in/orders/{$orderId}";
    
        $responseHdfc = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($apiKey),
            'version'       => $version,
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'x-merchantid'  => $merchantId,
            'x-customerid'  => $customerId,
        ])->get($url);
    
        if (! $responseHdfc->successful()) {
            Log::error('HDFC Order Status API Failed', [
                'order_id' => $orderId,
                'response' => $responseHdfc->body(),
            ]);
    
            return $response
                ->setError()
                ->setMessage('Unable to fetch order status from HDFC');
        }
    
        $orderResponse = $responseHdfc->json();
    
        // -------------------------------------------------
        // 3️⃣ Payment must be CHARGED
        // -------------------------------------------------
        if (($orderResponse['status'] ?? null) !== 'CHARGED') {
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->setMessage('Payment not successful');
        }
    
        // -------------------------------------------------
        // 4️⃣ Resolve original order ID (udf1)
        // -------------------------------------------------
        $originalOrderId = data_get($orderResponse, 'udf1');
    
        if (! $originalOrderId) {
            return $response
                ->setError()
                ->setMessage('Order reference missing');
        }
        
        
        // -------------------------------------------------
        // 5️⃣ Resolve or create customer
        // -------------------------------------------------
        $email = data_get($orderResponse, 'customer_email');
        $phone = data_get($orderResponse, 'customer_phone');
    
        $customer = Customer::query()
            ->when($email, fn ($q) => $q->where('email', $email))
            ->when(! $email && $phone, fn ($q) => $q->where('phone', $phone))
            ->first();
    
        if (! $customer) {
            $customer = Customer::create([
                'name' => data_get($orderResponse, 'card.name_on_card', 'Customer'),
                'email' => $email,
                'phone' => $phone,
                'password' => bcrypt(Str::random(16)),
                'confirmed_at' => now(),
            ]);
        }
    
        Auth::guard('customer')->login($customer);
    // dd($response);
        // -------------------------------------------------
        // 6️⃣ Trigger payment processed (MATCHES ORIGINAL)
        // -------------------------------------------------
        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'amount' => (float) $orderResponse['amount'],
            'currency' => 'INR',
            'charge_id' => $orderResponse['txn_id'],
            'payment_channel' => PAYSTACK_PAYMENT_METHOD_NAME,
            'status' => PaymentStatusEnum::COMPLETED,
            'customer_id' => $customer->id,
            'customer_type' => Customer::class,
            'payment_type' => 'direct',
            'order_id' => (array) $originalOrderId,
            'gateway_response' => $orderResponse,
        ], $request);
        $orderIds = explode(',', (string) $originalOrderId);
        /** @var \Botble\Ecommerce\Models\Order $order */
        $order = app(OrderInterface::class)->findById($orderIds[0]);
        $redirectUrl = PaymentHelper::getRedirectURL();
        // example: /checkout/{token}/success
        $path = parse_url($redirectUrl, PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));

        $checkoutToken = $segments[1] ?? null;
        $order->token = $checkoutToken;
        $order->save();
        // dd($order);
        // -------------------------------------------------
        // 7️⃣ Redirect success
        // -------------------------------------------------
        return $response
            ->setNextUrl(PaymentHelper::getRedirectURL())
            ->setMessage(__('Checkout successfully!'));
    }
}
