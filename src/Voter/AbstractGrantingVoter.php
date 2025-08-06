<?php

namespace SkriptManufaktur\SimpleRestBundle\Voter;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use TypeError;

abstract class AbstractGrantingVoter implements VoterInterface
{
    /**
     * @param TokenInterface $token
     * @param mixed          $subject
     * @param array<mixed>   $attributes
     * @param Vote|null      $vote
     *
     * @return int
     */
    public function vote(TokenInterface $token, mixed $subject, array $attributes, Vote|null $vote = null): int
    {
        if (null !== $vote) {
            $vote->result = self::ACCESS_ABSTAIN;
        }

        // we don't actually care about an attribute, as it is store in the stamp, anyway
        if (!$this->validateSubject($subject)) {
            return self::ACCESS_ABSTAIN;
        }

        [$envelope, $stamp] = $subject;

        try {
            // abstain vote by default in case none of the attributes are supported
            if (!$this->supports($stamp, $envelope)) {
                return self::ACCESS_ABSTAIN;
            }
        } catch (TypeError $e) {
            if (!str_contains($e->getMessage(), 'supports(): Argument #1')) {
                throw $e;
            }
        }

        if ($this->voteOnEnvelope($stamp, $envelope, $token, $vote)) {
            if (null !== $vote) {
                $vote->result = self::ACCESS_GRANTED;
            }

            // grant access as soon as at least one attribute returns a positive response
            return self::ACCESS_GRANTED;
        }

        if (null !== $vote) {
            $vote->result = self::ACCESS_DENIED;
        }

        // default is to deny access
        return self::ACCESS_DENIED;
    }

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param GrantingStampInterface $stamp
     * @param Envelope               $envelope
     *
     * @return bool
     */
    abstract protected function supports(GrantingStampInterface $stamp, Envelope $envelope): bool;

    /**
     * Perform a single access check operation on a given stamp, envelope and token.
     * It is safe to assume that $stamp and $envelope already passed the "supports()" method check.
     *
     * @param GrantingStampInterface $stamp
     * @param Envelope               $envelope
     * @param TokenInterface         $token
     * @param Vote|null              $vote
     *
     * @return bool
     */
    abstract protected function voteOnEnvelope(GrantingStampInterface $stamp, Envelope $envelope, TokenInterface $token, Vote|null $vote): bool;

    /**
     * @param mixed $subject
     *
     * @return bool
     */
    private function validateSubject(mixed $subject): bool
    {
        if (!is_array($subject)) {
            return false;
        }

        if (2 !== count($subject)) {
            return false;
        }

        if (!$subject[0] instanceof Envelope) {
            return false;
        }

        if (!$subject[1] instanceof GrantingStampInterface) {
            return false;
        }

        return true;
    }
}
