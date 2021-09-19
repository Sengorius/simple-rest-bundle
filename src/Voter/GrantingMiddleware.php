<?php

namespace SkriptManufaktur\SimpleRestBundle\Voter;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class GrantingMiddleware
 */
class GrantingMiddleware implements MiddlewareInterface
{
    private AuthorizationCheckerInterface $authChecker;


    /**
     * GrantingMiddleware constructor.
     *
     * @param AuthorizationCheckerInterface $authChecker
     */
    public function __construct(AuthorizationCheckerInterface $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    /**
     * @param Envelope       $envelope
     * @param StackInterface $stack
     *
     * @return Envelope
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $stamps = $envelope->all(GrantingStamp::class);
        $message = $envelope->getMessage();

        /** @var GrantingStamp $stamp */
        foreach ($stamps as $stamp) {
            if (!$this->authChecker->isGranted($stamp->getAttribute(), $message)) {
                throw new AccessDeniedException();
            }
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
