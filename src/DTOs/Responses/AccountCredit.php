<?php

namespace Sapak\Sms\DTOs\Responses;

/**
 * DTO for the "Get Account Credit" response.
 *
 * Encapsulates the response from the API, ensuring a strongly-typed result.
 */
class AccountCredit
{
    /**
     * @param float $credit The account credit balance (API type: double).
     */
    public function __construct(
        public readonly float $credit
    ) {}
}
