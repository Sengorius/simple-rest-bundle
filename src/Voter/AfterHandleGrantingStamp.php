<?php

namespace SkriptManufaktur\SimpleRestBundle\Voter;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

final readonly class AfterHandleGrantingStamp implements NonSendableStampInterface, GrantingStampInterface
{
    public function __construct(private string $attribute, private bool|null $vote = null)
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
