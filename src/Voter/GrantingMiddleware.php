<?php

namespace SkriptManufaktur\SimpleRestBundle\Voter;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class GrantingMiddleware implements MiddlewareInterface
{
    private AuthorizationCheckerInterface $authChecker;


    public function __construct(AuthorizationCheckerInterface $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // vote before firing the handlers
        $stamps = $envelope->all(GrantingStamp::class);

        /** @var GrantingStamp $stamp */
        foreach ($stamps as $stamp) {
            if (!$this->authChecker->isGranted($stamp->getAttribute(), $envelope)) {
                throw new AccessDeniedException();
            }
        }

        // execute stack and fire handlers with that
        $nextEnvelope = $stack->next()->handle($envelope, $stack);

        // vote again after handling
        $afterStamps = $nextEnvelope->all(AfterHandleGrantingStamp::class);

        /** @var AfterHandleGrantingStamp $stamp */
        foreach ($afterStamps as $stamp) {
            if (!$this->authChecker->isGranted($stamp->getAttribute(), $nextEnvelope)) {
                throw new AccessDeniedException();
            }
        }

        return $nextEnvelope;
    }
}
