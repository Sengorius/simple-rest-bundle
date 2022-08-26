<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Fixtures;

use Exception;
use SkriptManufaktur\SimpleRestBundle\Voter\AfterHandleGrantingStamp;
use SkriptManufaktur\SimpleRestBundle\Voter\GrantingStamp;
use SkriptManufaktur\SimpleRestBundle\Voter\GrantingStampInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DefaultVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['access', 'edit'], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!is_array($subject)) {
            throw new Exception('System Error: Subject for GrantingVoter is not an array!');
        }

        if (2 !== count($subject)) {
            throw new Exception('System Error: Subject must be an array with 2 values!');
        }

        [$envelope, $stamp] = $subject;

        if (!$envelope instanceof Envelope) {
            throw new Exception('System Error: Subject for GrantingVoter is malformed! Missing Envelope.');
        }

        if (!$stamp instanceof GrantingStampInterface) {
            throw new Exception('System Error: Subject for GrantingVoter is malformed! Missing GrantingStamp.');
        }

        if ($stamp instanceof AfterHandleGrantingStamp) {
            return !empty($envelope->all(HandledStamp::class));
        }

        if ($stamp instanceof GrantingStamp) {
            return 'access' === $stamp->getAttribute();
        }

        return false;
    }
}
