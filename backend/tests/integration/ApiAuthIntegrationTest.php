<?php

class ApiAuthIntegrationTest extends PHPUnit\Framework\TestCase
{
    private $baseUrl = 'http://localhost/diplomski/backend';


    public function testPublicProductsEndpoint()
    {
        $url = $this->baseUrl . '/products';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertContains($httpCode, [200, 400, 500]);
    }

    public function testPublicCategoriesEndpoint()
    {
        $url = $this->baseUrl . '/categories';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertEquals(200, $httpCode);
    }

    public function testProductsEndpointReturnsJson()
    {
        $url = $this->baseUrl . '/products';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($response, true);
        $this->assertIsArray($decoded);
    }

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

        $responseTime = ($endTime - $startTime) * 1000; 
        curl_close($ch);

        $this->assertLessThan(5000, $responseTime);
    }
}
