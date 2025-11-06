<?php

namespace Sapak\Sms\DTOs\Requests;

use DateTimeInterface;
use InvalidArgumentException;
use Morilog\Jalali\Jalalian;

/**
 * DTO for the "Find Received Messages" endpoint.
 * Converts Gregorian DateTime values to the Jalali format expected by the API.
 */
class FindMessages
{
    private const MAX_PAGE_SIZE = 100;

    public function __construct(
        public readonly ?int $pageNumber = null,
        public readonly ?int $pageSize = null,
        public readonly ?string $smsNumber = null,
        public readonly ?DateTimeInterface $fromDate = null,
        public readonly ?DateTimeInterface $toDate = null
    ) {
        $this->validate();
    }

    /**
     * Validate DTO values before sending.
     *
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        if ($this->pageSize !== null && $this->pageSize > self::MAX_PAGE_SIZE) {
            throw new InvalidArgumentException(
                sprintf('The "pageSize" cannot exceed %d.', self::MAX_PAGE_SIZE)
            );
        }
    }

    /**
     * Convert the DTO into an associative array for query parameters.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_filter([
            'pageNumber' => $this->pageNumber,
            'pageSize' => $this->pageSize,
            'smsNumber' => $this->smsNumber,
            'fromDate' => $this->toApiFormat($this->fromDate),
            'toDate' => $this->toApiFormat($this->toDate),
        ]);
    }

    /**
     * Convert DateTimeInterface to the Jalali date-time format.
     *
     * @param DateTimeInterface|null $date
     * @return string|null
     */
    private function toApiFormat(?DateTimeInterface $date): ?string
    {
        return $date ? Jalalian::fromDateTime($date)->format('datetime') : null;
    }
}
