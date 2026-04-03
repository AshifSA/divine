<?php

// ================= 1. READ HDFC CALLBACK =================
$params = $_POST;

// Debug / log
file_put_contents(
    __DIR__ . '/hdfc_callback.log',
    date('Y-m-d H:i:s') . ' CALLBACK ' . json_encode($params) . PHP_EOL,
    FILE_APPEND
);

// ================= 2. VALIDATE =================
if (empty($params['order_id'])) {
    http_response_code(400);
    exit('Order ID missing');
}

// ================= 3. BUILD QUERY STRING =================
// Pass ONLY the received POST data
$queryString = http_build_query([
    'gateway' => 'hdfc',
    'order_id' => $params['order_id'],
    'status' => $params['status'] ?? null,
    'status_id' => $params['status_id'] ?? null,
    'signature' => $params['signature'] ?? null,
    'signature_algorithm' => $params['signature_algorithm'] ?? null,
]);

// ================= 4. REDIRECT TO LARAVEL =================
$laravelCallbackUrl = 'https://divinetarangasilks.com/paystack/payment/callback';

$redirectUrl = $laravelCallbackUrl . '?' . $queryString;

// 302 redirect
header('Location: ' . $redirectUrl);
exit;
