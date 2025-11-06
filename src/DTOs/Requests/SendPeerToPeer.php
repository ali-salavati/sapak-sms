<?php

namespace Sapak\Sms\DTOs\Requests;

use InvalidArgumentException;
use Sapak\Sms\DTOs\Concerns\ValidatesSendAt;

/**
 * DTO for a single item in the "Send Message (Peer-to-Peer)" request array.
 * Validates data before sending to the API.
 */
class SendPeerToPeer
{
    use ValidatesSendAt;

    public function __construct(
        public readonly string $sender,
        public readonly string $recipient,
        public readonly string $message,
        public readonly ?string $sendAt = null
    ) {
        $this->validate();
    }

    /**
     * Validate the DTO properties.
     *
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        if (empty(trim($this->sender))) {
            throw new InvalidArgumentException('P2P "sender" cannot be empty.');
        }

        if (empty(trim($this->recipient))) {
            throw new InvalidArgumentException('P2P "recipient" cannot be empty.');
        }

        if (empty(trim($this->message))) {
            throw new InvalidArgumentException('P2P "message" cannot be empty.');
        }

        $this->validateSendAt($this->sendAt);
    }

    /**
     * Convert the DTO to an array suitable for the API.
     */
    public function toArray(): array
    {
        return [
            'sender' => $this->sender,
            'recipient' => $this->recipient,
            'message' => $this->message,
            'sendAt' => $this->sendAt,
        ];
    }
}
