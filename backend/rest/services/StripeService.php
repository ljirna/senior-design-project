<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/PaymentDao.php';
require_once __DIR__ . '/../dao/OrderDao.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/StreamHttpClient.php';

class StripeService
{
    private $stripe;
    private $orderDao;
    private $paymentDao;
    private $currency;
    private $supportedCurrencies = ['usd', 'eur', 'gbp', 'aud', 'cad', 'sek', 'nok', 'dkk', 'bam'];
    private $currencyFallback = 'eur';

    public function __construct()
    {
        // Load Stripe config
        $config = require __DIR__ . '/../../stripe.php';
        \Stripe\Stripe::setApiKey($config['secret_key']);

        // Use stream-based HTTP client as fallback when cURL is not available
        if (!function_exists('curl_version')) {
            \Stripe\ApiRequestor::setHttpClient(new \StripeCustom\StreamHttpClient());
        }

        $requested = strtolower($config['currency'] ?? 'usd');
        if (in_array($requested, $this->supportedCurrencies, true)) {
            $this->currency = $requested;
        } else {
            // Fallback to default currency
            $this->currency = $this->currencyFallback;
        }

        $this->orderDao = new OrderDao();
        $this->paymentDao = new PaymentDao();
    }

    /**
     * Create a Stripe Payment Intent
     */
    public function createPaymentIntent($order_id, $user_id)
    {
        // Get order details
        $order = $this->orderDao->getOrderById($order_id);
        if (!$order) {
            throw new Exception("Order not found");
        }

        // Verify order belongs to user
        if ($order['user_id'] != $user_id) {
            throw new Exception("Order does not belong to user");
        }

        // Verify order is pending
        if ($order['status'] != 'pending') {
            throw new Exception("Order is already processed");
        }

        try {
            // Create Payment Intent (try once, fallback to EUR on currency errors)
            $paymentIntent = null;
            $attemptCurrency = $this->currency;
            $attempts = 0;
            while ($attempts < 2) {
                try {
                    $paymentIntent = \Stripe\PaymentIntent::create([
                        'amount' => $order['total_amount'] * 100, // Convert to cents
                        'currency' => $attemptCurrency,
                        'metadata' => [
                            'order_id' => $order_id,
                            'user_id' => $user_id
                        ],
                        'description' => 'Order #' . $order_id . ' - Furniture Purchase',
                    ]);
                    // success — update used currency if fallback used
                    if ($attemptCurrency !== $this->currency) {
                        $this->currency = $attemptCurrency;
                    }
                    break;
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    $msg = $e->getMessage();
                    // If error mentions currency or not supported, retry with fallback
                    if ($attemptCurrency !== $this->currencyFallback && (stripos($msg, 'currency') !== false || stripos($msg, 'not supported') !== false)) {
                        $attemptCurrency = $this->currencyFallback;
                        $attempts++;
                        continue;
                    }
                    // rethrow other errors
                    throw $e;
                }
            }
            if (!$paymentIntent) {
                throw new Exception("Failed to create payment intent");
            }

            // Save Stripe payment intent ID to order
            try {
                $this->orderDao->update([
                    'stripe_payment_intent_id' => $paymentIntent->id
                ], $order_id, 'order_id');
            } catch (Exception $updateError) {
                // Log but don't fail if update fails
                error_log("Failed to update order with payment intent ID: " . $updateError->getMessage());
            }

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount / 100,
                'currency' => $paymentIntent->currency
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new Exception("Stripe error: " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Error creating payment intent: " . $e->getMessage());
        }
    }

    /**
     * Create a Stripe Payment Intent from a cart draft (no order persisted yet)
     */
    public function createPaymentIntentFromDraft($draft, $user_id)
    {
        $totalAmount = ($draft['totals']['subtotal'] ?? 0) + ($draft['totals']['delivery_total'] ?? 0) + ($draft['totals']['assembly_total'] ?? 0);
        if ($totalAmount <= 0) {
            throw new Exception("Invalid amount for payment intent");
        }

        $itemsForMetadata = [];
        if (!empty($draft['items']) && is_array($draft['items'])) {
            foreach ($draft['items'] as $item) {
                if (isset($item['product_id'], $item['quantity'])) {
                    $itemsForMetadata[] = [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ];
                }
            }
        }

        $metadata = [
            'user_id' => (string)$user_id,
            'delivery_type' => $draft['delivery_type'] ?? 'store_pickup',
            'assembly_option' => $draft['assembly_option'] ?? 'package',
            'shipping_address' => $draft['shipping_address'] ?? '',
            'items' => json_encode($itemsForMetadata),
        ];

        try {
            $paymentIntent = null;
            $attemptCurrency = $this->currency;
            $attempts = 0;

            while ($attempts < 2) {
                try {
                    $paymentIntent = \Stripe\PaymentIntent::create([
                        'amount' => (int)round($totalAmount * 100),
                        'currency' => $attemptCurrency,
                        'metadata' => $metadata,
                        'description' => 'Furniture purchase',
                    ]);

                    if ($attemptCurrency !== $this->currency) {
                        $this->currency = $attemptCurrency;
                    }
                    break;
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    $msg = $e->getMessage();
                    if ($attemptCurrency !== $this->currencyFallback && (stripos($msg, 'currency') !== false || stripos($msg, 'not supported') !== false)) {
                        $attemptCurrency = $this->currencyFallback;
                        $attempts++;
                        continue;
                    }
                    throw $e;
                }
            }

            if (!$paymentIntent) {
                throw new Exception("Failed to create payment intent");
            }

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount / 100,
                'currency' => $paymentIntent->currency,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new Exception("Stripe error: " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Error creating payment intent: " . $e->getMessage());
        }
    }

    /**
     * Map Stripe payment status to database-compatible status (single char)
     */
    private function mapStripeStatus($stripeStatus)
    {
        $statusMap = [
            'succeeded' => 'p',
            'processing' => 'r',
            'requires_action' => 'w',
            'requires_payment_method' => 'w',
            'requires_confirmation' => 'w',
            'canceled' => 'f'
        ];

        return $statusMap[$stripeStatus] ?? 'w';
    }

    /**
     * Confirm payment with card details
     */
    public function confirmPayment($payment_intent_id, $payment_method_id)
    {
        try {
            // Retrieve the payment intent first
            $paymentIntent = \Stripe\PaymentIntent::retrieve($payment_intent_id);

            // Check if payment intent is already succeeded or doesn't need confirmation
            if ($paymentIntent->status === 'succeeded') {
                // Record intent on order, leave order status for admin to finalize
                $this->orderDao->update([
                    'stripe_payment_intent_id' => $payment_intent_id
                ], $paymentIntent->metadata->order_id, 'order_id');

                return [
                    'status' => $paymentIntent->status,
                    'amount_paid' => $paymentIntent->amount / 100,
                    'message' => 'Payment received; your order will be processed soon.'
                ];
            }

            // Confirm the payment intent with the provided payment method if not already confirmed
            if (in_array($paymentIntent->status, ['requires_payment_method', 'requires_action'])) {
                $paymentIntent = $paymentIntent->confirm(
                    [
                        'payment_method' => $payment_method_id,
                    ]
                );
            }

            // Get payment method details (optional fetch, not stored)
            $paymentMethod = \Stripe\PaymentMethod::retrieve($payment_method_id);

            if ($paymentIntent->status === 'succeeded') {
                // Record intent on order, leave order status for admin to finalize
                $this->orderDao->update([
                    'stripe_payment_intent_id' => $payment_intent_id
                ], $paymentIntent->metadata->order_id, 'order_id');
            }

            return [
                'status' => $paymentIntent->status,
                'amount_paid' => $paymentIntent->amount / 100,
                'message' => 'Payment received; your order will be processed soon.'
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new Exception("Payment failed: " . $e->getMessage());
        }
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus($payment_intent_id)
    {
        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($payment_intent_id);

            return [
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount / 100,
                'currency' => $paymentIntent->currency,
                'charges' => $paymentIntent->charges->data[0] ?? null
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new Exception("Failed to check payment status: " . $e->getMessage());
        }
    }

    /**
     * Retrieve a payment intent by ID
     */
    public function retrievePaymentIntent($payment_intent_id)
    {
        try {
            return \Stripe\PaymentIntent::retrieve($payment_intent_id);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new Exception("Failed to retrieve payment intent: " . $e->getMessage());
        }
    }

    /**
     * Get Stripe configuration
     */
    public function getConfig()
    {
        $config = require __DIR__ . '/../../stripe.php';
        return [
            'publishableKey' => trim($config['publishable_key']),
            'currency' => strtolower($config['currency'] ?? 'usd')
        ];
    }
}
