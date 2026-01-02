<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/rest/services/StreamHttpClient.php';

header('Content-Type: application/json');

try {
    $config = require __DIR__ . '/stripe.php';
    \Stripe\Stripe::setApiKey($config['secret_key']);

    // Use stream client
    \Stripe\ApiRequestor::setHttpClient(new \StripeCustom\StreamHttpClient());

    // Try to create a simple payment intent
    $intent = \Stripe\PaymentIntent::create([
        'amount' => 1000,
        'currency' => 'usd',
        'description' => 'Test payment'
    ]);

    echo json_encode([
        'success' => true,
        'intent_id' => $intent->id,
        'status' => $intent->status
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
