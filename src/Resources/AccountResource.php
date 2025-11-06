<?php

namespace Sapak\Sms\Resources;

use Sapak\Sms\DTOs\Responses\AccountCredit;
use Sapak\Sms\Exceptions\ApiException;
use Sapak\Sms\Exceptions\AuthenticationException;
use Sapak\Sms\Exceptions\ValidationException;

/**
 * Handles API interactions related to the user account (e.g., credit).
 */
class AccountResource extends AbstractResource
{
    /**
     * Retrieve the current account credit balance.
     *
     * @return AccountCredit DTO containing the credit amount.
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function getCredit(): AccountCredit
    {
        $response = $this->request('get', 'users/me/credit');

        $data = json_decode($response->getBody()->getContents(), true);

        if (!isset($data['credit']) || !is_numeric($data['credit'])) {
            throw new ApiException(
                'Invalid response format from credit endpoint. "credit" key missing or not numeric.'
            );
        }

        return new AccountCredit(
            credit: (float) $data['credit']
        );
    }
}
