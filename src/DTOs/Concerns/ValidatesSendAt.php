<?php

namespace Sapak\Sms\DTOs\Concerns;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Trait ValidatesSendAt
 *
 * Provides reusable validation logic for the 'sendAt' timestamp property.
 */
trait ValidatesSendAt
{
    /**
     * Validate the 'sendAt' value.
     *
     * @param string|null $sendAt ISO-8601 (ATOM) timestamp or null.
     * @throws InvalidArgumentException If the timestamp is invalid.
     */
    private function validateSendAt(?string $sendAt): void
    {
        if (is_null($sendAt)) {
            return;
        }

        // 1. Validate format (strict RFC3339 / ATOM)
        // createFromFormat() is used for strict validation — new DateTime() is too permissive.
        $date = DateTime::createFromFormat(DateTimeInterface::ATOM, $sendAt);
        if ($date === false) {
            throw new InvalidArgumentException(
                'Invalid "sendAt" format. Expected a valid ATOM/RFC3339 timestamp (e.g. 2024-10-25T10:30:00+03:30).'
            );
        }

        // 2. Ensure the date is not in the past
        $now = new DateTime();
        if ($date < $now) {
            throw new InvalidArgumentException('"sendAt" cannot be in the past.');
        }

        // 3. Ensure the date is not more than one year in the future
        $maxFutureDate = (new DateTime())->modify('+1 year');
        if ($date > $maxFutureDate) {
            throw new InvalidArgumentException('"sendAt" cannot be more than one year in the future.');
        }
    }
}
