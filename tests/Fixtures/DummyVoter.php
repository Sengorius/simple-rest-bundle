<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Fixtures;

use SkriptManufaktur\SimpleRestBundle\Voter\AfterHandleGrantingStamp;
use SkriptManufaktur\SimpleRestBundle\Voter\GrantingStamp;
use SkriptManufaktur\SimpleRestBundle\Voter\GrantingStampInterface;
use SkriptManufaktur\SimpleRestBundle\Voter\AbstractGrantingVoter;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class DummyVoter extends AbstractGrantingVoter
{
    protected function supports(GrantingStampInterface $stamp, Envelope $envelope): bool
    {
        return in_array($stamp->getAttribute(), ['access', 'edit'], true);
    }

    protected function voteOnEnvelope(GrantingStampInterface $stamp, Envelope $envelope, TokenInterface $token, Vote|null $vote): bool
    {
        if ($stamp instanceof AfterHandleGrantingStamp) {
            $vote?->addReason('After handling grant');

            return !empty($envelope->all(HandledStamp::class));
        }

        if ($stamp instanceof GrantingStamp) {
            $vote?->addReason('Simple granting');

            return 'access' === $stamp->getAttribute();
        }

        $vote?->addReason('No stamp matched');

        return false;
    }
}
