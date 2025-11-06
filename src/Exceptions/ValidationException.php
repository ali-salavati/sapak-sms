<?php

namespace Sapak\Sms\Exceptions;

use Exception;

/**
 * Thrown for 400 (Bad Request) or 422 (Unprocessable Entity) errors.
 */
class ValidationException extends Exception
{
}