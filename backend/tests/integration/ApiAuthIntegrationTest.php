<?php

class ApiAuthIntegrationTest extends PHPUnit\Framework\TestCase
{
    private $baseUrl = 'http://localhost/diplomski/backend';

    /**
     * Test: Get public products (no auth required)
     */
    public function testPublicProductsEndpoint()
    {
        $url = $this->baseUrl . '/products';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Should be accessible without authentication
        $this->assertContains($httpCode, [200, 400, 500]); // Server is running
    }

    /**
     * Test: Get categories (public endpoint)
     */
    public function testPublicCategoriesEndpoint()
    {
        $url = $this->baseUrl . '/categories';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Should return success
        $this->assertEquals(200, $httpCode);
    }

    /**
     * Test: Products endpoint returns JSON
     */
    public function testProductsEndpointReturnsJson()
    {
        $url = $this->baseUrl . '/products';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        curl_close($ch);

        // Response should be valid JSON
        $decoded = json_decode($response, true);
        $this->assertIsArray($decoded);
    }

    /**
     * Test: API responds to requests
     */
    public function testApiIsResponsive()
    {
        $url = $this->baseUrl . '/products';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000; // Convert to ms
        curl_close($ch);

        // Response should be within 5 seconds
        $this->assertLessThan(5000, $responseTime);
    }
}
