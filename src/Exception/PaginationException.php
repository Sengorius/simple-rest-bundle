<?php

namespace SkriptManufaktur\SimpleRestBundle\Exception;

use RuntimeException;
use Throwable;

class PaginationException extends RuntimeException
{
    const EXCEPTION_CODE = 333;

    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, self::EXCEPTION_CODE, $previous);
    }
}
