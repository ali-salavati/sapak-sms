<?php

namespace Sapak\Sms\DTOs\Requests;

use InvalidArgumentException;
use Sapak\Sms\DTOs\Concerns\ValidatesSendAt;

/**
 * DTO for sending messages (one-to-many).
 * Validates data before sending to the API.
 */
class SendMessage
{
    use ValidatesSendAt;

    private const MAX_RECIPIENTS = 100;

    public function __construct(
        public readonly string $from,
        public readonly array $to,
        public readonly string $text,
        public readonly bool $isFlash = false,
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
        if (empty(trim($this->from))) {
            throw new InvalidArgumentException('Sender "from" cannot be empty.');
        }

        if (empty($this->to)) {
            throw new InvalidArgumentException('Recipients "to" array cannot be empty.');
        }

        if (count($this->to) > self::MAX_RECIPIENTS) {
            throw new InvalidArgumentException(
                sprintf('Cannot send to more than %d recipients in a single request.', self::MAX_RECIPIENTS)
            );
        }

        if (empty(trim($this->text))) {
            throw new InvalidArgumentException('Message "text" cannot be empty.');
        }

        $this->validateSendAt($this->sendAt);
    }

    /**
     * Convert the DTO to an array suitable for the API.
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'text' => $this->text,
            'isFlash' => $this->isFlash,
            'sendAt' => $this->sendAt,
        ];
    }
}
