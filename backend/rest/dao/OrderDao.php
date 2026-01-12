<?php
require_once __DIR__ . '/BaseDao.php';

class OrderDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct("orders");
    }

    // Get order by ID with user details
    public function getOrderById($order_id)
    {
        $stmt = $this->connection->prepare("
            SELECT o.*, 
                   u.full_name as customer_name,
                   u.email as customer_email,
                   u.phone_number as customer_phone
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            WHERE o.order_id = :order_id
        ");
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Get orders by user ID
    public function getOrdersByUserId($user_id, $limit = 20, $offset = 0)
    {
        $stmt = $this->connection->prepare("
            SELECT o.*,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
            FROM orders o
            WHERE o.user_id = :user_id
            ORDER BY o.order_date DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Get all orders with user info (Admin)
    public function getAllOrders($limit = 20, $offset = 0)
    {
        $stmt = $this->connection->prepare("
            SELECT o.*, 
                   u.full_name as customer_name,
                   u.email as customer_email,
                   u.phone_number as customer_phone,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count,
                   COALESCE((SELECT SUM(quantity * price) FROM order_items WHERE order_id = o.order_id), 0) as subtotal
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            ORDER BY o.order_date DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Get order items with product details
    public function getOrderItems($order_id)
    {
        $stmt = $this->connection->prepare("
            SELECT oi.*,
                   p.name as product_name,
                   p.description as product_description,
                   p.delivery_fee_override,
                   p.assembly_fee_override,
                   (oi.quantity * oi.price) as item_total
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = :order_id
            ORDER BY oi.order_item_id
        ");
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Create order with items
    public function createOrderWithItems($order_data, $items)
    {
        $this->connection->beginTransaction();

        try {
            // Insert order
            $columns = implode(", ", array_keys($order_data));
            $placeholders = ":" . implode(", :", array_keys($order_data));
            $sql = "INSERT INTO orders ($columns) VALUES ($placeholders)";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($order_data);
            $order_id = $this->connection->lastInsertId();

            // Insert order items
            foreach ($items as $item) {
                $item['order_id'] = $order_id;
                $columns = implode(", ", array_keys($item));
                $placeholders = ":" . implode(", :", array_keys($item));
                $sql = "INSERT INTO order_items ($columns) VALUES ($placeholders)";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute($item);
            }

            $this->connection->commit();
            return $order_id;
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    // Update order status
    public function updateOrderStatus($order_id, $status)
    {
        $stmt = $this->connection->prepare("
            UPDATE orders 
            SET status = :status 
            WHERE order_id = :order_id
        ");
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':status', $status);
        return $stmt->execute();
    }

    // Get order statistics
    public function getOrderStatistics($user_id = null)
    {
        $sql = "
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as average_order_value,
                MIN(order_date) as first_order_date,
                MAX(order_date) as last_order_date
            FROM orders
            WHERE status = 'completed'
        ";

        if ($user_id) {
            $sql .= " AND user_id = :user_id";
        }

        $stmt = $this->connection->prepare($sql);

        if ($user_id) {
            $stmt->bindParam(':user_id', $user_id);
        }

        $stmt->execute();
        return $stmt->fetch();
    }

    // Get orders by status
    public function getOrdersByStatus($status, $limit = 20, $offset = 0)
    {
        $stmt = $this->connection->prepare("
            SELECT o.*, u.full_name as user_name
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            WHERE o.status = :status
            ORDER BY o.order_date DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Search orders
    public function searchOrders($search_term, $limit = 20, $offset = 0)
    {
        $stmt = $this->connection->prepare("
            SELECT o.*, u.full_name as user_name, u.email as user_email
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            WHERE o.order_id LIKE :search 
               OR u.full_name LIKE :search 
               OR u.email LIKE :search
               OR o.shipping_address LIKE :search
            ORDER BY o.order_date DESC
            LIMIT :limit OFFSET :offset
        ");
        $search = "%" . $search_term . "%";
        $stmt->bindParam(':search', $search);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Count orders
    public function countOrders($user_id = null)
    {
        $sql = "SELECT COUNT(*) as total FROM orders";
        if ($user_id) {
            $sql .= " WHERE user_id = :user_id";
        }

        $stmt = $this->connection->prepare($sql);
        if ($user_id) {
            $stmt->bindParam(':user_id', $user_id);
        }
        $stmt->execute();
        return $stmt->fetch()['total'];
    }
}
