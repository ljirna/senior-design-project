<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/PaymentDao.php';

class PaymentService extends BaseService
{
    public function __construct()
    {
        $dao = new PaymentDao();
        parent::__construct($dao);
    }

    public function getPaymentById($payment_id)
    {
        return $this->dao->getPaymentById($payment_id);
    }

    public function getOrderPayments($order_id)
    {
        return $this->dao->getOrderPayments($order_id);
    }

    public function getUserPayments($user_id, $limit = 20, $offset = 0)
    {
        return [];
    }

    public function updatePaymentStatus($payment_id, $status)
    {
        $valid_statuses = ['pending', 'completed', 'failed', 'refunded', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            throw new Exception("Invalid payment status");
        }

        $payment = $this->dao->getById($payment_id);
        if (!$payment) {
            throw new Exception("Payment not found");
        }

        return $this->dao->update(['payment_status' => $status], $payment_id, 'payment_id');
    }
}
