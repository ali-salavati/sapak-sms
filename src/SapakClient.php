<?php

namespace Sapak\Sms;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Sapak\Sms\Resources\MessageResource;

/**
 * Main entry point for the Sapak SMS SDK.
 *
 * Handles API configuration, authentication, and provides access to resources.
 */
class SapakClient
{
    private const DEFAULT_BASE_URI = 'https://api.sapak.me/v1/';

    protected ClientInterface $httpClient;

    /**
     * Create a new SapakClient instance.
     *
     * @param string $apiKey Your SAPAK API key.
     * @param string|null $baseUri Optional custom base URI (for testing/staging).
     * @param array $guzzleConfig Optional Guzzle configuration (e.g., custom handler for mocks).
     */
    public function __construct(
        private readonly string $apiKey,
        ?string $baseUri = null,
        array $guzzleConfig = []
    ) {
        // Default configuration (headers, timeout, base URI)
        $defaultConfig = [
            'base_uri' => $baseUri ?? self::DEFAULT_BASE_URI,
            'headers' => $this->getDefaultHeaders(),
            'timeout' => 5.0,
        ];

        // Merge custom headers (SDK defaults take precedence)
        $customHeaders = $guzzleConfig['headers'] ?? [];
        $defaultConfig['headers'] = $customHeaders + $defaultConfig['headers'];

        // Merge custom config (user-provided options override defaults)
        $finalConfig = $guzzleConfig + $defaultConfig;
        $finalConfig['headers'] = $defaultConfig['headers'];

        $this->httpClient = new Client($finalConfig);
    }

    /**
     * Get default request headers for all API calls.
     *
     * @return array<string, string>
     */
    private function getDefaultHeaders(): array
    {
        return [
            'X-API-KEY' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Access message-related API endpoints.
     *
     * @return MessageResource
     */
    public function messages(): MessageResource
    {
        return new MessageResource($this->httpClient);
    }
}
