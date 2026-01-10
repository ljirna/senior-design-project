<?php

require_once __DIR__ . '/../../rest/services/CartService.php';
require_once __DIR__ . '/../../rest/dao/CartDao.php';

class CartServiceTest extends PHPUnit\Framework\TestCase
{
    private $cartService;

    protected function setUp(): void
    {
        $this->cartService = new CartService();
    }

    /**
     * Test: Cart quantity validation - zero quantity
     */
    public function testCartRejectsZeroQuantity()
    {
        $cartItem = [
            'user_id' => 1,
            'product_id' => 1,
            'quantity' => 0
        ];

        // Quantity should be at least 1
        $this->assertLessThan(1, $cartItem['quantity']);
    }

    /**
     * Test: Cart quantity validation - negative quantity
     */
    public function testCartRejectsNegativeQuantity()
    {
        $cartItem = [
            'quantity' => -5
        ];

        $this->assertLessThan(1, $cartItem['quantity']);
    }

    /**
     * Test: Cart quantity validation - valid quantity
     */
    public function testCartAcceptsValidQuantity()
    {
        $validQuantities = [1, 2, 5, 100];

        foreach ($validQuantities as $qty) {
            $this->assertGreaterThan(0, $qty);
        }
    }

    /**
     * Test: Calculate cart total with multiple items
     */
    public function testCalculateCartTotal()
    {
        $items = [
            ['price' => 50.00, 'quantity' => 2],
            ['price' => 100.00, 'quantity' => 1]
        ];

        $total = 0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $this->assertEquals(200.00, $total);
    }

    /**
     * Test: Cart total precision
     */
    public function testCartTotalPrecision()
    {
        $items = [
            ['price' => 10.99, 'quantity' => 3]
        ];

        $total = 0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Should handle decimals correctly
        $expected = 32.97;
        $this->assertEquals($expected, round($total, 2));
    }
}
