<?php

namespace Sapak\Sms\DTOs\Responses;

/**
 * DTO for the items in the successful "send" and "status" responses.
 */
class SentMessageStatus
{
    /** @var int Delivered to handset */
    public const STATUS_DELIVERED = 1;
    /** @var int Not delivered to handset */
    public const STATUS_UNDELIVERED = 2;
    /** @var int Did not reach operator */
    public const STATUS_NOT_REACHED_OPERATOR = 3;
    /** @var int Pending in operator */
    public const STATUS_PENDING_IN_OPERATOR = 4;
    /** @var int Delivered to operator */
    public const STATUS_DELIVERED_TO_OPERATOR = 5;
    /** @var int Unknown */
    public const STATUS_UNKNOWN = 6;
    /** @var int Sent (Generic) */
    public const STATUS_SENT = 7;
    /** @var int Blacklisted by operator */
    public const STATUS_BLACKLISTED = 8;
    /** @var int Queued for sending */
    public const STATUS_QUEUED = 9;
    /** @var int Not sent */
    public const STATUS_NOT_SENT = 10;
    /** @var int Sending */
    public const STATUS_SENDING = 11;
    /** @var int Awaiting confirmation */
    public const STATUS_AWAITING_CONFIRMATION = 12;
    /** @var int Invalid recipient */
    public const STATUS_INVALID_RECIPIENT = 13;
    /** @var int Recipient blocked */
    public const STATUS_RECIPIENT_BLOCKED = 14;
    /** @var int Halted */
    public const STATUS_HALTED = 15;
    /** @var int Rejected by operator */
    public const STATUS_REJECTED_BY_OPERATOR = 16;
    /** @var int Recipient unsubscribed */
    public const STATUS_UNSUBSCRIBED = 17;
    /** @var int Outside allowed sending hours */
    public const STATUS_OUTSIDE_HOURS = 18;


    /**
     * @param int $id The message ID (bigint)
     * @param int $status The status code (e.g., self::STATUS_QUEUED)
     */
    public function __construct(
        public readonly int $id,
        public readonly int $status
    ) {}

    /**
     * Get a human-readable text for the current status.
     *
     * @return string
     */
    public function getStatusText(): string
    {
        return match ($this->status) {
            self::STATUS_DELIVERED => 'Delivered to handset',
            self::STATUS_UNDELIVERED => 'Not delivered to handset',
            self::STATUS_NOT_REACHED_OPERATOR => 'Did not reach operator',
            self::STATUS_PENDING_IN_OPERATOR => 'Pending in operator',
            self::STATUS_DELIVERED_TO_OPERATOR => 'Delivered to operator',
            self::STATUS_UNKNOWN => 'Unknown',
            self::STATUS_SENT => 'Sent',
            self::STATUS_BLACKLISTED => 'Blacklisted by operator',
            self::STATUS_QUEUED => 'Queued for sending',
            self::STATUS_NOT_SENT => 'Not sent',
            self::STATUS_SENDING => 'Sending',
            self::STATUS_AWAITING_CONFIRMATION => 'Awaiting confirmation',
            self::STATUS_INVALID_RECIPIENT => 'Invalid recipient',
            self::STATUS_RECIPIENT_BLOCKED => 'Recipient blocked',
            self::STATUS_HALTED => 'Halted',
            self::STATUS_REJECTED_BY_OPERATOR => 'Rejected by operator',
            self::STATUS_UNSUBSCRIBED => 'Recipient unsubscribed',
            self::STATUS_OUTSIDE_HOURS => 'Outside allowed sending hours',
            default => 'Undefined Status Code',
        };
    }
}