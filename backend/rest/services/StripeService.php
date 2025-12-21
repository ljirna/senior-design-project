<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/PaymentDao.php';
require_once __DIR__ . '/../dao/OrderDao.php';
require_once __DIR__ . '/../../vendor/autoload.php';

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
            $this->orderDao->update($order_id, [
                'stripe_payment_intent_id' => $paymentIntent->id
            ]);

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount / 100,
                'currency' => $paymentIntent->currency
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new Exception("Stripe error: " . $e->getMessage());
        }
    }

    /**
     * Confirm payment with card details
     */
    public function confirmPayment($payment_intent_id, $payment_method_id)
    {
        try {
            // Attach payment method to payment intent
            $paymentIntent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            $paymentIntent = \Stripe\PaymentIntent::update($payment_intent_id, [
                'payment_method' => $payment_method_id,
            ]);

            // Confirm the payment
            //$paymentIntent = \Stripe\PaymentIntent::confirm($payment_intent_id);

            // Get payment method details
            $paymentMethod = \Stripe\PaymentMethod::retrieve($payment_method_id);

            // Prepare payment data for database
            $payment_data = [
                'order_id' => $paymentIntent->metadata->order_id,
                'payment_method' => 'credit_card',
                'amount' => $paymentIntent->amount / 100,
                'payment_status' => $paymentIntent->status,
                'payment_date' => date('Y-m-d H:i:s'),
                'stripe_payment_intent_id' => $payment_intent_id,
                'stripe_customer_id' => $paymentIntent->customer ?? null,
                'card_last4' => $paymentMethod->card->last4 ?? null,
                'card_brand' => $paymentMethod->card->brand ?? null,
                'receipt_url' => $paymentIntent->charges->data[0]->receipt_url ?? null
            ];

            // Create payment record
            $payment_id = $this->paymentDao->createPaymentWithStripe($payment_data);

            // Update order status if payment succeeded
            if ($paymentIntent->status == 'succeeded') {
                $this->orderDao->updateOrderStatus($paymentIntent->metadata->order_id, 'approved');
                $this->orderDao->update($paymentIntent->metadata->order_id, [
                    'stripe_payment_status' => 'completed'
                ]);
            }

            return [
                'payment_id' => $payment_id,
                'status' => $paymentIntent->status,
                'amount_paid' => $paymentIntent->amount / 100,
                'receipt_url' => $paymentIntent->charges->data[0]->receipt_url ?? null
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
