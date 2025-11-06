<?php

namespace Sapak\Sms;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Sapak\Sms\Resources\MessageResource;

/**
 * Main Entrypoint for the Sapak SMS SDK.
 */
class SapakClient
{
    private const DEFAULT_BASE_URI = 'https://api.sapak.me/v1/';

    protected ClientInterface $httpClient;

    /**
     * @param string $apiKey Your X-API-KEY
     * @param string|null $baseUri Optional base URI override for testing/staging.
     * @param ClientInterface|null $httpClient Optional custom Guzzle client.
     */
    public function __construct(
        private readonly string $apiKey,
        ?string $baseUri = null,
        ?ClientInterface $httpClient = null
    ) {
        if ($httpClient) {
            $this->httpClient = $httpClient;
            return;
        }

        $this->httpClient = new Client([
            'base_uri' => $baseUri ?? self::DEFAULT_BASE_URI,
            'headers' => [
                'X-API-KEY' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'timeout' => 5.0, // A reasonable default timeout
        ]);
    }

    /**
     * Access the Message resources (send, status, receive).
     */
    public function messages(): MessageResource
    {
        // This is the "Resource" pattern. The client delegates
        // work to specialized classes.
        return new MessageResource($this->httpClient);
    }
}