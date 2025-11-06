<?php

namespace Sapak\Sms\Resources;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Sapak\Sms\DTOs\Requests\SendMessage;
use Sapak\Sms\DTOs\Requests\SendPeerToPeer;
use Sapak\Sms\DTOs\Responses\SentMessageStatus;
use Sapak\Sms\Exceptions\ApiException;
use Sapak\Sms\Exceptions\AuthenticationException;
use Sapak\Sms\Exceptions\ValidationException;

/**
 * Handles all API interactions related to messages.
 * Examples: sending messages, checking status, receiving messages.
 */
class MessageResource extends AbstractResource
{
    /**
     * Send a message to one or more recipients (one-to-many).
     *
     * @param SendMessage $message DTO containing message parameters.
     * @return SentMessageStatus[] Array of DTOs with message ID and status.
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function send(SendMessage $message): array
    {
        $payload = $message->toArray();

        // Send request using the abstracted method from AbstractResource
        $response = $this->request('post', 'messages', [
            'json' => $payload
        ]);

        return $this->handleSendResponse($response);
    }

    /**
     * Send multiple peer-to-peer messages in a single batch.
     *
     * @param SendPeerToPeer[] $messages Array of P2P DTOs.
     * @return SentMessageStatus[] Array of DTOs with message ID and status.
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws InvalidArgumentException If the input array is invalid.
     */
    public function sendPeerToPeer(array $messages): array
    {
        // Validate input array
        if (empty($messages)) {
            throw new InvalidArgumentException('Input array cannot be empty.');
        }
        if (count($messages) > 100) {
            throw new InvalidArgumentException('Cannot send more than 100 P2P messages in a single request.');
        }

        // Map DTOs to payload array
        $payload = array_map(function (SendPeerToPeer $dto) {
            if (! $dto instanceof SendPeerToPeer) {
                throw new InvalidArgumentException('All items must be instances of SendPeerToPeer.');
            }
            return $dto->toArray();
        }, $messages);

        // Send request
        $response = $this->request('post', 'messages/p2p', [
            'json' => $payload
        ]);

        return $this->handleSendResponse($response);
    }

    /**
     * Parse successful 200 OK response for send and sendPeerToPeer methods.
     *
     * @param ResponseInterface $response The API response.
     * @return SentMessageStatus[] Array of DTOs with message ID and status.
     */
    private function handleSendResponse(ResponseInterface $response): array
    {
        $data = json_decode($response->getBody()->getContents(), true);

        return array_map(function (array $item) {
            return new SentMessageStatus(
                id: $item['id'],
                status: $item['status']
            );
        }, $data);
    }
}
