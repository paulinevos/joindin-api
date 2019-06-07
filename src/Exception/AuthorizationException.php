<?php

declare(strict_types=1);

namespace Joindin\Api\Exception;

use \Exception;
use \Throwable;

final class AuthorizationException extends Exception implements Throwable
{
    private const MESSAGE = 'This operation requires %s privileges.';

    public static function forNonAdministrator(): self
    {
        return new static(sprintf(self::MESSAGE, 'admin'), 403);
    }
}
