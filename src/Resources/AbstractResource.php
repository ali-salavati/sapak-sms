<?php

namespace Sapak\Sms\Resources;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;
use Sapak\Sms\Exceptions\ApiException;
use Sapak\Sms\Exceptions\AuthenticationException;
use Sapak\Sms\Exceptions\ValidationException;

/**
 * Base class for all API resources.
 * Manages the authenticated HTTP client and centralizes exception handling.
 */
abstract class AbstractResource
{
    public function __construct(
        protected ClientInterface $httpClient
    ) {}

    /**
     * Perform an API request and handle Guzzle exceptions.
     *
     * @param string $method HTTP method (e.g., 'post', 'get').
     * @param string $uri API endpoint URI (e.g., 'messages').
     * @param array $options Guzzle request options (e.g., 'json', 'query').
     * @return ResponseInterface
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ValidationException
     */
    protected function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        try {
            return $this->httpClient->request($method, $uri, $options);

        } catch (ClientException $e) {
            // Handle 4xx client errors
            $this->handleApiErrors($e->getResponse());

        } catch (ServerException $e) {
            // Handle 5xx server errors
            $this->handleApiErrors($e->getResponse());

        } catch (GuzzleException $e) {
            // Handle other Guzzle errors (e.g., network issues)
            throw new ApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Translate 4xx/5xx HTTP responses into SDK-specific exceptions.
     *
     * @param ResponseInterface $response Guzzle error response.
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ValidationException
     */
    protected function handleApiErrors(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();
        $data = json_decode($response->getBody()->getContents(), true);
        $message = $data['message'] ?? 'Unknown API Error';

        match ($statusCode) {
            400, 422 => throw new ValidationException($message),
            401, 403 => throw new AuthenticationException($message),
            default => throw new ApiException($message, $statusCode),
        };
    }
}
