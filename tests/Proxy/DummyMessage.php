<?php

namespace Proxies\__CG__\SkriptManufaktur\SimpleRestBundle\Tests\Fixtures;

class DummyMessage
{
    public function __construct(private readonly string $message)
    {
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
