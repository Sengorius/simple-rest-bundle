<?php

namespace SkriptManufaktur\SimpleRestBundle\Voter;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class GrantingStamp implements NonSendableStampInterface, GrantingStampInterface
{
    private string $attribute;
    private ?bool $vote;


    public function __construct(string $attribute, ?bool $vote = null)
    {
        $this->attribute = $attribute;
        $this->vote = $vote;
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function getVote(): ?bool
    {
        return $this->vote;
    }
}
