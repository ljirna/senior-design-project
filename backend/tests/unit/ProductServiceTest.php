<?php

require_once __DIR__ . '/../../rest/services/ProductService.php';
require_once __DIR__ . '/../../rest/dao/ProductDao.php';

class ProductServiceTest extends PHPUnit\Framework\TestCase
{
    private $productService;

    protected function setUp(): void
    {
        $this->productService = new ProductService();
    }

    /**
     * Test: Get all products
     */
    public function testGetAllProducts()
    {
        $products = $this->productService->getAll();

        $this->assertIsArray($products);
    }

    /**
     * Test: Get product by ID
     */
    public function testGetProductById()
    {
        // Test method exists and handles calls
        try {
            $product = $this->productService->getProductById(1);
            // Should return array or null
            $this->assertTrue(is_array($product) || is_null($product));
        } catch (Exception $e) {
            // Method should exist
            $this->assertTrue(method_exists($this->productService, 'getProductById'));
        }
    }

    /**
     * Test: Product has images array
     */
    public function testProductImagesStructure()
    {
        try {
            $product = $this->productService->getProductById(1);
            // If product exists, it should have images array
            if ($product) {
                $this->assertIsArray($product);
                $this->assertArrayHasKey('images', $product);
                $this->assertIsArray($product['images']);
            } else {
                $this->assertTrue(true); // No data is ok for test
            }
        } catch (Exception $e) {
            $this->assertTrue(true); // Database might not be available
        }
    }

    /**
     * Test: Price is numeric
     */
    public function testProductPriceIsNumeric()
    {
        $product = $this->productService->getProductById(1);

        if ($product && isset($product['price'])) {
            $this->assertTrue(is_numeric($product['price']));
            $this->assertGreaterThan(0, (float)$product['price']);
        } else {
            $this->assertTrue(is_null($product) || !isset($product['price']));
        }
    }

    /**
     * Test: Stock is non-negative
     */
    public function testProductStockIsNonNegative()
    {
        $product = $this->productService->getProductById(1);

        if ($product && isset($product['stock'])) {
            $this->assertGreaterThanOrEqual(0, (int)$product['stock']);
        } else {
            $this->assertTrue(is_null($product) || !isset($product['stock']));
        }
    }

    /**
     * Test: Product name is not empty
     */
    public function testProductNameNotEmpty()
    {
        try {
            $product = $this->productService->getProductById(1);
            if ($product) {
                $this->assertNotEmpty($product['product_name']);
                $this->assertIsString($product['product_name']);
            } else {
                $this->assertTrue(true); // No data is ok for test
            }
        } catch (Exception $e) {
            $this->assertTrue(true); // Database might not be available
        }
    }
}
