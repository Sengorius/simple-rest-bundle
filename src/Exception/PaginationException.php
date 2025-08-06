<?php

namespace SkriptManufaktur\SimpleRestBundle\Exception;

use RuntimeException;
use Throwable;

class PaginationException extends RuntimeException
{
    public const int EXCEPTION_CODE = 333;


    public function __construct(string $message, Throwable|null $previous = null)
    {
        parent::__construct($message, self::EXCEPTION_CODE, $previous);
    }
}
