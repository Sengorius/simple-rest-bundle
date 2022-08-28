<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Voter;

use PHPUnit\Framework\TestCase;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DefaultVoter;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyMessage;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyVoter;
use SkriptManufaktur\SimpleRestBundle\Voter\GrantingStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class GrantingVoterTest extends TestCase
{
    private TokenInterface $token;
    private DummyVoter $grantingVoter;
    private AccessDecisionManager $manager;


    protected function setUp(): void
    {
        parent::setUp();

        $this->token = $this->createMock(TokenInterface::class);
        $this->grantingVoter = new DummyVoter();
        $this->manager = new AccessDecisionManager([$this->grantingVoter, new DefaultVoter()]);
    }

    public function testSuccessfulGranting(): void
    {
        $stamp = new GrantingStamp('access');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        static::assertTrue($this->decide([$envelope, $stamp]));
        static::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote([$envelope, $stamp]));
    }

    public function testFailedGranting(): void
    {
        $stamp = new GrantingStamp('edit');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        static::assertFalse($this->decide([$envelope, $stamp]));
        static::assertSame(VoterInterface::ACCESS_DENIED, $this->vote([$envelope, $stamp]));
    }

    public function testUnnoticedGranting(): void
    {
        $stamp = new GrantingStamp('delete');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        static::assertFalse($this->decide([$envelope, $stamp]));
        static::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote([$envelope, $stamp]));
    }

    public function testSubjectNotAnArray(): void
    {
        $stamp = new GrantingStamp('access');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        static::assertFalse($this->decide($envelope));
        static::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote($envelope));
    }

    public function testSubjectIsWrongArrayCount(): void
    {
        static::assertFalse($this->decide([]));
        static::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote([]));
    }

    public function testSubjectIsMissingEnvelope(): void
    {
        static::assertFalse($this->decide([null, null]));
        static::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote([null, null]));
    }

    public function testSubjectIsMissingStamp(): void
    {
        $stamp = new GrantingStamp('access');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        static::assertFalse($this->decide([$envelope, null]));
        static::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote([$envelope, null]));
    }

    /**
     * This is a wrapper to make it easier, calling the manager
     * Our voter does not care about an $attribute, so it's left empty
     *
     * @param mixed $subject
     *
     * @return bool
     */
    private function decide(mixed $subject): bool
    {
        return $this->manager->decide($this->token, [''], $subject);
    }

    /**
     * This is a wrapper to make it easier, calling the Voter->vote()
     * Our voter does not care about an $attribute, so it's left empty
     *
     * @param mixed $subject
     *
     * @return int
     */
    private function vote(mixed $subject): int
    {
        return $this->grantingVoter->vote($this->token, $subject, ['']);
    }
}
