<?php

namespace Sapak\Sms\DTOs\Responses;

/**
 * DTO for the items in the successful "send" response array.
 */
class SentMessageStatus
{
    /**
     * @param int $id The message ID (bigint)
     * @param int $status The status code (e.g., 1 for queued)
     */
    public function __construct(
        public readonly int $id,
        public readonly int $status
    ) {}
}