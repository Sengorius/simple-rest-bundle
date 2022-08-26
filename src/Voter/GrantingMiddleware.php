<?php

namespace SkriptManufaktur\SimpleRestBundle\Voter;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class GrantingMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly AuthorizationCheckerInterface $authChecker, private readonly bool $throws = true)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // vote before firing the handlers
        $majorVote = true;
        $stamps = $envelope->all(GrantingStamp::class);
        $envelope = $envelope->withoutAll(GrantingStamp::class);

        /** @var GrantingStamp $stamp */
        foreach ($stamps as $stamp) {
            $vote = $this->authChecker->isGranted($stamp->getAttribute(), $envelope);
            $envelope = $envelope->with(new GrantingStamp($stamp->getAttribute(), $vote));
            $majorVote = $majorVote && $vote;
        }

        if ($this->throws && !$majorVote) {
            throw new AccessDeniedException();
        }

        // execute stack and fire handlers with that
        $nextEnvelope = $stack->next()->handle($envelope, $stack);

        // vote again after handling
        $majorVote = true;
        $afterStamps = $nextEnvelope->all(AfterHandleGrantingStamp::class);
        $nextEnvelope = $nextEnvelope->withoutAll(AfterHandleGrantingStamp::class);

        /** @var AfterHandleGrantingStamp $stamp */
        foreach ($afterStamps as $stamp) {
            $vote = $this->authChecker->isGranted($stamp->getAttribute(), $nextEnvelope);
            $nextEnvelope = $nextEnvelope->with(new AfterHandleGrantingStamp($stamp->getAttribute(), $vote));
            $majorVote = $majorVote && $vote;
        }

        if ($this->throws && !$majorVote) {
            throw new AccessDeniedException();
        }

        return $nextEnvelope;
    }
}
