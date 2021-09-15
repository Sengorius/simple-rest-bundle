<?php

namespace SkriptManufaktur\SimpleRestBundle\Exception;

use RuntimeException;
use Throwable;

/**
 * Class ApiBusException
 */
class ApiBusException extends RuntimeException
{
    const EXCEPTION_CODE = 331;

    /**
     * HandleBusException constructor.
     *
     * @param string         $message
     * @param Throwable|null $previous
     */
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, self::EXCEPTION_CODE, $previous);
    }
}
