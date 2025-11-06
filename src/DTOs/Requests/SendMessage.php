<?php

namespace Sapak\Sms\DTOs\Requests;

use DateTime;
use Exception;
use InvalidArgumentException;

/**
 * DTO (Data Transfer Object) for sending a new SMS message.
 * Validates input upon creation.
 */
class SendMessage
{
    /**
     * @param string $from Sender number.
     * @param string[] $to Array of recipient numbers (max 100).
     * @param string $text Message content.
     * @param bool $isFlash Whether the message is flash.
     * @param string|null $sendAt ISO-8601 datetime string for scheduled sending.
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly string $from,
        public readonly array $to,
        public readonly string $text,
        public readonly bool $isFlash = false,
        public readonly ?string $sendAt = null
    ) {
        // Guard clauses for input validation
        if (empty($from)) {
            throw new InvalidArgumentException('Sender "from" cannot be empty.');
        }
        if (empty($to)) {
            throw new InvalidArgumentException('Recipients "to" array cannot be empty.');
        }
        if (count($to) > 100) {
            throw new InvalidArgumentException('Cannot send to more than 100 recipients at once.');
        }
        if (empty($text)) {
            throw new InvalidArgumentException('Message "text" cannot be empty.');
        }

        // Validate scheduled send time
        if ($this->sendAt !== null) {
            $this->validateSendAt($this->sendAt);
        }
    }

    /**
     * Validate the sendAt datetime according to API rules.
     * 1. Must be ISO-8601 format.
     * 2. Cannot be more than one year in the future.
     * 3. Cannot be in the past (buffer of 5 minutes allowed).
     *
     * @param string $sendAt
     * @throws InvalidArgumentException
     */
    private function validateSendAt(string $sendAt): void
    {
        try {
            $sendAtDate = new DateTime($sendAt);
        } catch (Exception $e) {
            throw new InvalidArgumentException(
                'Invalid "sendAt" date format. Must be ISO-8601 (e.g., 2014-02-03T20:36:27Z).',
                0,
                $e
            );
        }

        $oneYearFromNow = (new DateTime())->modify('+1 year');
        if ($sendAtDate > $oneYearFromNow) {
            throw new InvalidArgumentException('Scheduled send time "sendAt" cannot be more than one year in the future.');
        }

        $fiveMinutesAgo = (new DateTime())->modify('-5 minutes');
        if ($sendAtDate < $fiveMinutesAgo) {
            throw new InvalidArgumentException('Scheduled send time "sendAt" cannot be in the past.');
        }
    }
}
