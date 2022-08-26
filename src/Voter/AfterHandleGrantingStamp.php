<?php

namespace SkriptManufaktur\SimpleRestBundle\Voter;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class AfterHandleGrantingStamp implements NonSendableStampInterface, GrantingStampInterface
{
    public function __construct(private readonly string $attribute, private readonly bool|null $vote = null)
    {
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function getVote(): bool|null
    {
        return $this->vote;
    }
}
