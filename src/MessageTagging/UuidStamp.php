<?php

namespace SkriptManufaktur\SimpleRestBundle\MessageTagging;

use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Component\Uid\UuidV7;

readonly class UuidStamp implements StampInterface
{
    public function __construct(private UuidV7 $uuid = new UuidV7())
    {
    }

    public function getUuid(): UuidV7
    {
        return $this->uuid;
    }
}
