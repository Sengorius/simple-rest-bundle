<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Voter;

use Exception;
use PHPUnit\Framework\TestCase;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DefaultVoter;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyMessage;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyVoter;
use SkriptManufaktur\SimpleRestBundle\Voter\GrantingStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;

class DefaultVoterTest extends TestCase
{
    private TokenInterface $token;
    private AccessDecisionManager $manager;


    protected function setUp(): void
    {
        parent::setUp();

        $this->token = $this->createMock(TokenInterface::class);
        $this->manager = new AccessDecisionManager([new DefaultVoter(), new DummyVoter()]);
    }

    public function testSuccessfulGranting(): void
    {
        $stamp = new GrantingStamp('access');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        static::assertTrue($this->decide($stamp->getAttribute(), [$envelope, $stamp]));
    }

    public function testFailedGranting(): void
    {
        $stamp = new GrantingStamp('edit');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        static::assertFalse($this->decide($stamp->getAttribute(), [$envelope, $stamp]));
    }

    public function testUnnoticedGranting(): void
    {
        $stamp = new GrantingStamp('delete');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        static::assertFalse($this->decide($stamp->getAttribute(), [$envelope, $stamp]));
    }

    public function testSubjectNotAnArray(): void
    {
        static::expectException(Exception::class);
        static::expectExceptionMessage('System Error: Subject for GrantingVoter is not an array!');

        $stamp = new GrantingStamp('access');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        $this->decide($stamp->getAttribute(), $envelope);
    }

    public function testSubjectIsWrongArrayCount(): void
    {
        static::expectException(Exception::class);
        static::expectExceptionMessage('System Error: Subject must be an array with 2 values!');

        $this->decide('access', []);
    }

    public function testSubjectIsMissingEnvelope(): void
    {
        static::expectException(Exception::class);
        static::expectExceptionMessage('System Error: Subject for GrantingVoter is malformed! Missing Envelope.');

        $this->decide('access', [null, null]);
    }

    public function testSubjectIsMissingStamp(): void
    {
        static::expectException(Exception::class);
        static::expectExceptionMessage('System Error: Subject for GrantingVoter is malformed! Missing GrantingStamp.');

        $stamp = new GrantingStamp('access');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        $this->decide($stamp->getAttribute(), [$envelope, null]);
    }

    /**
     * This is a wrapper to make it easier, calling the manager
     *
     * @param string $attribute
     * @param mixed  $subject
     *
     * @return bool
     */
    private function decide(string $attribute, mixed $subject): bool
    {
        return $this->manager->decide($this->token, [$attribute], $subject);
    }
}
