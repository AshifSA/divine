<?php

namespace Botble\Paystack\Providers;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Facades\PaymentMethods;
use Botble\Paystack\Forms\PaystackPaymentMethodForm;
use Botble\Paystack\Services\Gateways\PaystackPaymentService;
use Botble\Paystack\Services\Paystack;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Throwable;
use Botble\Ecommerce\Repositories\Interfaces\OrderAddressInterface;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, [$this, 'registerPaystackMethod'], 16, 2);
        $this->app->booted(function (): void {
            add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, [$this, 'checkoutWithPaystack'], 16, 2);
        });

        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, [$this, 'addPaymentSettings'], 97);

        add_filter(BASE_FILTER_ENUM_ARRAY, function ($values, $class) {
            if ($class == PaymentMethodEnum::class) {
                $values['PAYSTACK'] = PAYSTACK_PAYMENT_METHOD_NAME;
            }

            return $values;
        }, 21, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == PAYSTACK_PAYMENT_METHOD_NAME) {
                $value = 'Paystack';
            }

            return $value;
        }, 21, 2);

        add_filter(BASE_FILTER_ENUM_HTML, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == PAYSTACK_PAYMENT_METHOD_NAME) {
                $value = Html::tag(
                    'span',
                    PaymentMethodEnum::getLabel($value),
                    ['class' => 'label-success status-label']
                )
                    ->toHtml();
            }

            return $value;
        }, 21, 2);

        add_filter(PAYMENT_FILTER_GET_SERVICE_CLASS, function ($data, $value) {
            if ($value == PAYSTACK_PAYMENT_METHOD_NAME) {
                $data = PaystackPaymentService::class;
            }

            return $data;
        }, 20, 2);

        add_filter(PAYMENT_FILTER_PAYMENT_INFO_DETAIL, function ($data, $payment) {
            if ($payment->payment_channel == PAYSTACK_PAYMENT_METHOD_NAME) {
                $paymentService = (new PaystackPaymentService());
                $paymentDetail = $paymentService->getPaymentDetails($payment);
                if ($paymentDetail) {
                    $data = view(
                        'plugins/paystack::detail',
                        ['payment' => $paymentDetail, 'paymentModel' => $payment]
                    )->render();
                }
            }

            return $data;
        }, 20, 2);

        add_filter(PAYMENT_FILTER_GET_REFUND_DETAIL, function ($data, $payment, $refundId) {
            if ($payment->payment_channel == PAYSTACK_PAYMENT_METHOD_NAME) {
                $refundDetail = (new PaystackPaymentService())->getRefundDetails($refundId);
                if (! Arr::get($refundDetail, 'error')) {
                    $refunds = Arr::get($payment->metadata, 'refunds');
                    $refund = collect($refunds)->firstWhere('data.id', $refundId);
                    $refund = array_merge($refund, Arr::get($refundDetail, 'data', []));

                    return array_merge($refundDetail, [
                        'view' => view(
                            'plugins/paystack::refund-detail',
                            ['refund' => $refund, 'paymentModel' => $payment]
                        )->render(),
                    ]);
                }

                return $refundDetail;
            }

            return $data;
        }, 20, 3);
    }

    public function addPaymentSettings(?string $settings): string
    {
        return $settings . PaystackPaymentMethodForm::create()->renderForm();
    }

    public function registerPaystackMethod(?string $html, array $data): string
    {
        PaymentMethods::method(PAYSTACK_PAYMENT_METHOD_NAME, [
            'html' => view('plugins/paystack::methods', $data)->render(),
        ]);

        return $html;
    }

    public function checkoutWithPaystack(array $data, Request $request): array
    {
        if ($data['type'] !== PAYSTACK_PAYMENT_METHOD_NAME) {
            return $data;
        }

        $supportedCurrencies = (new PaystackPaymentService())->supportedCurrencyCodes();
        $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);
            $orderIds = (array) $request->input('order_id', []);
            $orderIdorg = Arr::first($orderIds);
            $orderAddress = $this->app->make(OrderAddressInterface::class)->getFirstBy(['order_id' => $orderIdorg]);
            
        if (! in_array($paymentData['currency'], $supportedCurrencies)) {
            $data['error'] = true;
            $data['message'] = __(
                ":name doesn't support :currency. List of currencies supported by :name: :currencies.",
                [
                    'name' => 'Paystack',
                    'currency' => $paymentData['currency'],
                    'currencies' => implode(', ', $supportedCurrencies),
                ]
            );

            return $data;
        }
        $orderId = $this->generateOrderId(); // FORCE SAFE ID
        $customerId = $this->generateCustomerId($paymentData['address']['email'] ?? '');
        $amount = number_format((float) ($paymentData['amount'] ?? 0), 2, '.', '');
        $currency = 'INR';
        $email = $paymentData['address']['email'] ?? '';
        $phone = $paymentData['address']['phone'] ?? '';
        $name = $paymentData['address']['name'] ?? 'Customer';
        $baseUrl = "https://smartgateway.hdfcuat.bank.in";
        $apiKey = "64932EB4F574FD39C2CEC25E618144";
        $merchantId = "SG4271";
        // $returnUrl = route('hdfc.return', ['order_id' => $orderId]);
        $returnUrl = "https://divinetarangasilks.com/public/hdfcapi.php";
        $headers = [
            'Authorization: Basic ' . base64_encode($apiKey . ':'), // REQUIRED
            'Content-Type: application/json',
            'x-merchantid: ' . $merchantId,
            'x-customerid: ' . $customerId,
        ];

        $requestData = [
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
        
            'payment_page_client_id' => 'hdfcmaster', // EXACT FROM HDFC
            'action' => 'paymentPage',
        
            'customer_id' => $customerId,
            'customer_email' => $email,
            'customer_phone' => $phone,
        
            'return_url' => $returnUrl,
            'description' => 'Complete your payment',
        
            'first_name' => $name,
            'last_name' => '',
            // ✅ IMPORTANT: pass original order id here
            'udf1' => (string) $orderIdorg,
            'metadata' => [
                'order_id' => $orderIdorg,
                'customer_id' => $paymentData['customer_id'],
                'customer_type' => $paymentData['customer_type'] ?? '',
            ],
        ];

        if (!$baseUrl || !$apiKey || !$merchantId) {
            dd('SmartGateway env values missing', $baseUrl, $apiKey, $merchantId);
        }
        
        $apiUrl = rtrim($baseUrl, '/') . '/session';
        
        // $headers = [
        //     'Authorization: Basic ' . base64_encode($apiKey),
        //     'Content-Type: application/json',
        //     'x-merchantid: ' . $merchantId,
        // ];
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // dd($response);
        if ($httpCode !== 200) {
            $data['error'] = true;
            $data['message'] = 'Unable to initiate payment';
            return $data;
        }
        $decoded = json_decode($response, true);
        // dd($decoded);
        if (! isset($decoded['payment_links']['web'])) {
            $data['error'] = true;
            $data['message'] = 'Invalid payment gateway response';
            return $data;
        }
        
        
        header('Location: ' . $decoded['payment_links']['web']);
        exit;

        //     do_action('payment_before_making_api_request', PAYSTACK_PAYMENT_METHOD_NAME, $requestData);

        //     $response = $payStack->getAuthorizationResponse($requestData);

        //     do_action('payment_after_api_response', PAYSTACK_PAYMENT_METHOD_NAME, $requestData, (array) $response);

        //     if ($response['status']) {
        //         header('Location: ' . $response['data']['authorization_url']);
        //         exit;
        //     }

        //     $data['error'] = true;
        //     $data['message'] = __('Payment failed!');
        // // } catch (Throwable $exception) {
        // //     $data['error'] = true;
        // //     $data['message'] = json_encode($exception->getMessage());

        // //     BaseHelper::logError($exception);
        // // }

        // return $data;
    }
    
     protected function generateOrderId()
    {
        return 'ORD' . substr(md5(uniqid('', true)), 0, 14);
    }

    protected function generateCustomerId($email)
    {
        return 'cust' . substr(md5($email), 0, 14);
    }
}
