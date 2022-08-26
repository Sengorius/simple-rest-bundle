<?php

namespace SkriptManufaktur\SimpleRestBundle\Voter;

use Exception;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use TypeError;

abstract class AbstractGrantingVoter implements VoterInterface
{
    /**
     * @param TokenInterface $token
     * @param mixed          $subject
     * @param array          $attributes
     *
     * @return int
     *
     * @throws Exception
     */
    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        // we don't actually care about an attribute, as it is store in the stamp, anyway
        $this->validateSubject($subject);
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

        if ($this->voteOnEnvelope($stamp, $envelope, $token)) {
            // grant access as soon as at least one attribute returns a positive response
            return self::ACCESS_GRANTED;
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
     *
     * @return bool
     */
    abstract protected function voteOnEnvelope(GrantingStampInterface $stamp, Envelope $envelope, TokenInterface $token): bool;

    /**
     * @param mixed $subject
     *
     * @throws Exception
     */
    private function validateSubject(mixed $subject): void
    {
        if (!is_array($subject)) {
            throw new Exception('System Error: Subject for GrantingVoter is not an array!');
        }

        if (2 !== count($subject)) {
            throw new Exception('System Error: Subject must be an array with 2 values!');
        }

        if (!$subject[0] instanceof Envelope) {
            throw new Exception('System Error: Subject for GrantingVoter is malformed! Missing Envelope.');
        }

        if (!$subject[1] instanceof GrantingStampInterface) {
            throw new Exception('System Error: Subject for GrantingVoter is malformed! Missing GrantingStamp.');
        }
    }
}
