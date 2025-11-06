<?php

namespace Sapak\Sms\Resources;

use GuzzleHttp\ClientInterface;

/**
 * Base class for all API resources.
 * Holds the authenticated HTTP client.
 */
abstract class AbstractResource
{
    public function __construct(
        protected ClientInterface $httpClient
    ) {}
}