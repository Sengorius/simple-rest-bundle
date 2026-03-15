<?php

namespace SkriptManufaktur\SimpleRestBundle\MessageTagging;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

readonly class UuidMiddleware implements MiddlewareInterface
{
    public function __construct(private UuidContextStack $context)
    {
    }

    /**
     * @param Envelope       $envelope
     * @param StackInterface $stack
     *
     * @return Envelope
     *
     * @throws ExceptionInterface
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (null === ($stamp = $envelope->last(UuidStamp::class))) {
            $envelope = $envelope->with($stamp = new UuidStamp());
        }

        $this->context->push($stamp->getUuid());

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            $this->context->pop();
        }
    }
}
