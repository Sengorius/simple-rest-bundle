<?php

namespace SkriptManufaktur\SimpleRestBundle\MessageTagging;

use Symfony\Component\Uid\UuidV7;

class UuidContextStack
{
    /** @var UuidV7[] */
    private array $uuidStack = [];


    public function push(UuidV7 $uuid): int
    {
        $this->uuidStack[] = $uuid;

        return count($this->uuidStack);
    }

    public function pop(): int
    {
        array_pop($this->uuidStack);

        return count($this->uuidStack);
    }

    /** @return UuidV7[] */
    public function head(): array
    {
        return $this->uuidStack;
    }
}
