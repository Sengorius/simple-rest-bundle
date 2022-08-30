<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Fixtures;

use SkriptManufaktur\SimpleRestBundle\Voter\AfterHandleGrantingStamp;
use SkriptManufaktur\SimpleRestBundle\Voter\GrantingStamp;
use SkriptManufaktur\SimpleRestBundle\Voter\GrantingStampInterface;
use SkriptManufaktur\SimpleRestBundle\Voter\AbstractGrantingVoter;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DummyVoter extends AbstractGrantingVoter
{
    protected function supports(GrantingStampInterface $stamp, Envelope $envelope): bool
    {
        return in_array($stamp->getAttribute(), ['access', 'edit'], true);
    }

    protected function voteOnEnvelope(GrantingStampInterface $stamp, Envelope $envelope, TokenInterface $token): bool
    {
        if ($stamp instanceof AfterHandleGrantingStamp) {
            return !empty($envelope->all(HandledStamp::class));
        }

        if ($stamp instanceof GrantingStamp) {
            return 'access' === $stamp->getAttribute();
        }

        return false;
    }
}
