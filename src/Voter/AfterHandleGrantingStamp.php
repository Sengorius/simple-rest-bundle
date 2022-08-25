<?php

namespace SkriptManufaktur\SimpleRestBundle\Voter;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class AfterHandleGrantingStamp implements NonSendableStampInterface
{
    private string $attribute;


    public function __construct(string $attribute)
    {
        $this->attribute = $attribute;
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }
}
