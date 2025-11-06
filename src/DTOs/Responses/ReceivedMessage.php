<?php

namespace Sapak\Sms\DTOs\Responses;

use DateTimeImmutable;

/**
 * DTO representing a single received message item from the "Find Messages" endpoint.
 *
 * This DTO ensures that data received from the API is strongly-typed,
 * converting the Jalali date string into a standard DateTimeImmutable object.
 */
class ReceivedMessage
{
    /**
     * @param int $id Unique message identifier.
     * @param DateTimeImmutable $date Message received timestamp (converted from Jalali).
     * @param string $body Message text content.
     * @param string $fromNumber Sender's phone number.
     * @param string $toNumber Receiver's phone number (user's own number).
     */
    public function __construct(
        public readonly int $id,
        public readonly DateTimeImmutable $date,
        public readonly string $body,
        public readonly string $fromNumber,
        public readonly string $toNumber
    ) {}
}
