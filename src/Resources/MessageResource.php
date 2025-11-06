<?php

namespace Sapak\Sms\Resources;

use InvalidArgumentException;
use Morilog\Jalali\Jalalian;
use Psr\Http\Message\ResponseInterface;
use Sapak\Sms\DTOs\Requests\FindMessages;
use Sapak\Sms\DTOs\Requests\SendMessage;
use Sapak\Sms\DTOs\Requests\SendPeerToPeer;
use Sapak\Sms\DTOs\Responses\ReceivedMessage;
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
     * @var int The maximum number of IDs that can be checked in a single status request.
     */
    private const MAX_STATUS_IDS = 100;

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
            return $dto->toArray();
        }, $messages);

        // Send request
        $response = $this->request('post', 'messages/p2p', [
            'json' => $payload
        ]);

        return $this->handleSendResponse($response);
    }

    /**
     * Retrieve received messages based on filters.
     *
     * @param FindMessages $filters DTO containing query parameters (pagination, filters).
     * @return ReceivedMessage[] An array of received message DTOs.
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function find(FindMessages $filters): array
    {
        $response = $this->request('get', 'messages/find', [
            'query' => $filters->toArray()
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return array_map(function (array $item) {
            $date = Jalalian::fromFormat('Y/m/d H:i:s', $item['date'])
                ->toCarbon()
                ->toDateTimeImmutable();

            return new ReceivedMessage(
                id: $item['id'],
                date: $date,
                body: $item['body'],
                fromNumber: $item['fromNumber'],
                toNumber: $item['toNumber']
            );
        }, $data);
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

    /**
     * Retrieve the status of multiple messages by their IDs.
     *
     * @param int[] $messageIds Array of message IDs (maximum 100 IDs per request).
     * @return SentMessageStatus[] Array of DTOs containing ID and status for each message.
     *
     * @throws InvalidArgumentException If the input array is empty, exceeds the limit, or contains non-integer values.
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function getStatuses(array $messageIds): array
    {
        // Client-side validation
        if (empty($messageIds)) {
            throw new InvalidArgumentException('Message IDs array cannot be empty.');
        }

        if (count($messageIds) > self::MAX_STATUS_IDS) {
            throw new InvalidArgumentException(
                sprintf('Cannot request more than %d statuses in a single request.', self::MAX_STATUS_IDS)
            );
        }

        // Ensure all IDs are integers
        foreach ($messageIds as $id) {
            if (!is_int($id)) {
                throw new InvalidArgumentException('All items in message IDs array must be integers.');
            }
        }

        // Send request to API
        $response = $this->request('post', 'messages/statuses', [
            'json' => $messageIds
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        // Map API response to SentMessageStatus DTOs
        return array_map(fn(array $item) => new SentMessageStatus(
            id: $item['messageId'], // Map API's "messageId" to our DTO's "id"
            status: $item['status']
        ), $data);
    }

}
