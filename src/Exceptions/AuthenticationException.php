<?php

namespace Sapak\Sms\Exceptions;

use Exception;

/**
 * Thrown for 401 (Unauthorized) or 403 (Forbidden) errors.
 */
class AuthenticationException extends Exception
{
}