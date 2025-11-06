<?php

namespace Sapak\Sms\Resources;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Sapak\Sms\DTOs\Requests\SendMessage;
use Sapak\Sms\DTOs\Responses\SentMessageStatus;
use Sapak\Sms\Exceptions\ApiException;
use Sapak\Sms\Exceptions\AuthenticationException;
use Sapak\Sms\Exceptions\ValidationException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;

/**
 * Handles all API endpoints related to messages.
 */
class MessageResource extends AbstractResource
{
    /**
     * Send a "one-to-many" message.
     * Corresponds to POST /v1/messages
     *
     * @param SendMessage $message The validated DTO containing message details.
     * @return SentMessageStatus[] Array of status DTOs, one per recipient.
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws GuzzleException
     */
    public function send(SendMessage $message): array
    {
        try {
            $response = $this->httpClient->post('messages', [
                // Guzzle automatically serializes public properties of the DTO
                'json' => $message
            ]);

            return $this->mapResponseToSentMessageStatus($response);

        } catch (ClientException $e) {
            // 4xx Errors
            $this->handleClientError($e);
        } catch (ServerException $e) {
            // 5xx Errors
            throw new ApiException("API server error: " . $e->getMessage(), $e->getCode(), $e);
        } catch (Exception $e) {
            // Other Guzzle/network errors
            throw new ApiException("Network or client error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Maps the 4xx HTTP errors to our custom exceptions.
     * @throws ValidationException
     * @throws AuthenticationException
     * @throws ApiException
     */
    private function handleClientError(ClientException $e): void
    {
        $response = $e->getResponse();
        $statusCode = $response->getStatusCode();
        $body = json_decode($response->getBody()->getContents(), true);
        $message = $body['message'] ?? 'Unknown client error';

        match ($statusCode) {
            400 => throw new ValidationException($message, $statusCode, $e),
            401, 403 => throw new AuthenticationException($message, $statusCode, $e),
            404 => throw new ApiException("Endpoint not found: " . $e->getRequest()->getUri(), $statusCode, $e),
            default => throw new ApiException($message, $statusCode, $e),
        };
    }

    /**
     * Maps the 200 OK response body to an array of DTOs.
     *
     * @return SentMessageStatus[]
     */
    private function mapResponseToSentMessageStatus(ResponseInterface $response): array
    {
        $data = json_decode($response->getBody()->getContents(), true);

        // Map the raw array to an array of DTOs
        return array_map(
            fn (array $item) => new SentMessageStatus(
                $item['id'],
                $item['status']
            ),
            $data
        );
    }
}