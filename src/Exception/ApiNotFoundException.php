<?php

namespace SkriptManufaktur\SimpleRestBundle\Exception;

use RuntimeException;
use Throwable;

class ApiNotFoundException extends RuntimeException
{
    const EXCEPTION_CODE = 404;

    public function __construct(string $message, Throwable|null $previous = null)
    {
        parent::__construct($message, self::EXCEPTION_CODE, $previous);
    }
}
