<?php
require_once __DIR__ . '/BaseDao.php';

class PaymentDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct("payments");
    }

    public function getPaymentById($payment_id) {
        $stmt = $this->connection->prepare("
            SELECT p.*,
                   o.order_id,
                   o.total_amount as order_total,
                   o.status as order_status,
                   u.full_name as user_name
            FROM payments p
            JOIN orders o ON p.order_id = o.order_id
            JOIN users u ON o.user_id = u.user_id
            WHERE p.payment_id = :payment_id
        ");
        $stmt->bindParam(':payment_id', $payment_id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getOrderPayments($order_id) {
        $stmt = $this->connection->prepare("
            SELECT * FROM payments 
            WHERE order_id = :order_id 
            ORDER BY payment_date DESC
        ");
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createPaymentWithStripe($payment_data) {
        $columns = implode(", ", array_keys($payment_data));
        $placeholders = ":" . implode(", :", array_keys($payment_data));
        $sql = "INSERT INTO payments ($columns) VALUES ($placeholders)";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($payment_data);
        return $this->connection->lastInsertId();
    }

    public function updatePaymentWithStripe($payment_id, $stripe_data) {
        $stmt = $this->connection->prepare("
            UPDATE payments 
            SET payment_status = :status,
                stripe_payment_intent_id = :payment_intent_id,
                stripe_customer_id = :customer_id,
                stripe_session_id = :session_id,
                card_last4 = :card_last4,
                card_brand = :card_brand,
                receipt_url = :receipt_url,
                payment_date = NOW()
            WHERE payment_id = :payment_id
        ");
        
        $stmt->bindParam(':payment_id', $payment_id);
        $stmt->bindParam(':status', $stripe_data['status']);
        $stmt->bindParam(':payment_intent_id', $stripe_data['payment_intent_id']);
        $stmt->bindParam(':customer_id', $stripe_data['customer_id']);
        $stmt->bindParam(':session_id', $stripe_data['session_id']);
        $stmt->bindParam(':card_last4', $stripe_data['card_last4']);
        $stmt->bindParam(':card_brand', $stripe_data['card_brand']);
        $stmt->bindParam(':receipt_url', $stripe_data['receipt_url']);
        
        return $stmt->execute();
    }

    public function getPaymentByStripeIntentId($payment_intent_id) {
        $stmt = $this->connection->prepare("
            SELECT * FROM payments 
            WHERE stripe_payment_intent_id = :payment_intent_id
        ");
        $stmt->bindParam(':payment_intent_id', $payment_intent_id);
        $stmt->execute();
        return $stmt->fetch();
    }
}