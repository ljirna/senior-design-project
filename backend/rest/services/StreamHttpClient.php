<?php

namespace StripeCustom;

/**
 * Custom HTTP client for Stripe that uses PHP streams instead of cURL
 * This is a fallback when cURL extension is not available
 */
class StreamHttpClient implements \Stripe\HttpClient\ClientInterface
{
    private $timeout = 80;
    private $connectTimeout = 30;

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $method = strtoupper($method);

        // Prepare request body
        $body = '';
        if (!empty($params)) {
            if ($method === 'GET') {
                $absUrl .= (strpos($absUrl, '?') === false ? '?' : '&') . http_build_query($params);
            } else {
                $body = http_build_query($params);
            }
        }

        // Build headers list - Stripe SDK passes headers as indexed array with formatted strings
        $headersList = [];
        foreach ($headers as $key => $value) {
            // If headers are already formatted as strings (indexed array), use them directly
            if (is_numeric($key) && is_string($value)) {
                $headersList[] = $value;
            } else {
                // Otherwise, format as key: value
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $headersList[] = "$key: $value";
            }
        }

        // Add content headers for POST/PUT
        if (($method === 'POST' || $method === 'PUT' || $method === 'PATCH') && !empty($body)) {
            // Only add if not already present
            $hasContentType = false;
            foreach ($headersList as $header) {
                if (stripos($header, 'Content-Type:') === 0) {
                    $hasContentType = true;
                    break;
                }
            }
            if (!$hasContentType) {
                $headersList[] = 'Content-Type: application/x-www-form-urlencoded';
            }
            $headersList[] = 'Content-Length: ' . strlen($body);
        }

        // Create stream context
        $opts = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headersList),
                'content' => $body,
                'timeout' => $this->timeout,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,  // Disable SSL verification for development
                'verify_peer_name' => false
            ]
        ];

        $context = stream_context_create($opts);

        // Make the request
        $response = @file_get_contents($absUrl, false, $context);

        if ($response === false) {
            $error = error_get_last();
            throw new \Stripe\Exception\ApiConnectionException(
                'Could not connect to Stripe: ' . ($error['message'] ?? 'Unknown error')
            );
        }

        // Parse response headers
        $statusCode = 200;
        $responseHeaders = [];

        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $statusCode = (int)$matches[1];
                } else {
                    $parts = explode(':', $header, 2);
                    if (count($parts) === 2) {
                        $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                    }
                }
            }
        }

        // Ensure we always return an array for headers
        if (!is_array($responseHeaders)) {
            $responseHeaders = [];
        }

        return [$response, $statusCode, $responseHeaders];
    }
}
