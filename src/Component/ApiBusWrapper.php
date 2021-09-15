<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use SkriptManufaktur\SimpleRestBundle\Exception\ApiBusException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Class ApiBusWrapper
 */
class ApiBusWrapper
{
    const TYPE_NULL = 'null';
    const TYPE_BOOL = 'bool';
    const TYPE_INT = 'int';
    const TYPE_DOUBLE = 'double';
    const TYPE_STRING = 'string';
    const TYPE_ARRAY = 'array';

    private MessageBusInterface $bus;


    /**
     * ApiBusWrapper constructor.
     *
     * @param MessageBusInterface $bus
     */
    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Redirect the dispatch of a given message
     *
     * @param object|Envelope  $message
     * @param StampInterface[] $stamps
     *
     * @return Envelope
     */
    public function dispatch($message, array $stamps = []): Envelope
    {
        return $this->bus->dispatch($message, $stamps);
    }

    /**
     * Simplifies the handle method to get the result from last handler
     *
     * @param Envelope $envelope      The envelope after sending the message via MessageBus
     * @param array    $expectedTypes An array of types defining the expected return types from a handler
     *
     * @return mixed|null Returns values with following priority
     *     - false - if the handler did not provide a result, the test on expected types failed or the message is sent to asynchronous transport
     *     - mixed|null - the result, if anything went just fine
     *
     * @throws ApiBusException
     */
    public function checkMessageResult(Envelope $envelope, array $expectedTypes = [])
    {
        // stop here, if the message will be sent to an asynchronous transport
        if (null !== $envelope->last(SentStamp::class)) {
            return false;
        }

        // get the last handled stamp to receive a result
        /** @var HandledStamp $stamp */
        if (null === ($stamp = $envelope->last(HandledStamp::class))) {
            throw new ApiBusException(sprintf('Message "%s" did not return anything from handler!', get_class($envelope->getMessage())));
        }

        // test the result against the expected types
        $result = $stamp->getResult();

        if (!$this->expectedTypesValid($result, $expectedTypes)) {
            throw new ApiBusException(sprintf(
                'Message "%s" did not have a stamp with expected value within types [%s]!',
                get_class($envelope->getMessage()),
                implode(', ', $expectedTypes)
            ));
        }

        return $result;
    }

    /**
     * Simplifies the handle method to get results from all handlers
     *
     * @param Envelope $envelope      The envelope after sending the message via MessageBus
     * @param array    $expectedTypes An array of types defining the expected return types from a handler
     *
     * @return HandledStamp[]|false Returns values with following priority
     *     - false - if the test on expected types failed or the message is sent to asynchronous transport
     *     - array - a list of results, if anything went just fine
     *
     * @throws ApiBusException
     */
    public function checkAllMessageResults(Envelope $envelope, array $expectedTypes = [])
    {
        // stop here, if the message will be sent to an asynchronous transport
        if (null !== $envelope->last(SentStamp::class)) {
            return false;
        }

        // get all handled stamps to receive a result
        $stamps = $envelope->all(HandledStamp::class);
        $results = [];

        /** @var HandledStamp $stamp */
        foreach ($stamps as $stamp) {
            // test the result against the expected types
            if (!$this->expectedTypesValid($stamp->getResult(), $expectedTypes)) {
                throw new ApiBusException(sprintf(
                    'Message "%s" did not have a stamp with expected value within types [%s]!',
                    get_class($envelope->getMessage()),
                    implode(', ', $expectedTypes)
                ));
            }

            $results[] = $stamp;
        }

        return $results;
    }

    /**
     * Simplifies the handle method to get only expected results from all handlers
     *
     * @param Envelope $envelope      The envelope after sending the message via MessageBus
     * @param array    $expectedTypes An array of types defining the expected return types from a handler
     *
     * @return HandledStamp[]
     *
     * @throws ApiBusException
     */
    public function filterAllMessageResults(Envelope $envelope, array $expectedTypes = []): array
    {
        // stop here, if the message will be sent to an asynchronous transport
        if (null !== $envelope->last(SentStamp::class)) {
            return [];
        }

        return array_filter(
            $envelope->all(HandledStamp::class),
            fn (HandledStamp $stamp) => $this->expectedTypesValid($stamp->getResult(), $expectedTypes)
        );
    }

    /**
     * Compare the result on a handle with the expected type
     *
     * @param mixed $result
     * @param array $expectedTypes
     *
     * @return bool
     *
     * @throws ApiBusException
     */
    private function expectedTypesValid($result, array $expectedTypes): bool
    {
        // if nothing is expected, anything is fine
        if (empty($expectedTypes)) {
            return true;
        }

        // any expected type has to be described as a string, e.g. null == 'null'
        foreach ($expectedTypes as $type) {
            if (!is_string($type)) {
                throw new ApiBusException('Only strings or null are allowed to compare expected types for message handling!');
            }
        }

        $isNullType = is_null($result) && in_array(self::TYPE_NULL, $expectedTypes);
        $isBoolType = is_bool($result) && in_array(self::TYPE_BOOL, $expectedTypes);
        $isIntType = is_int($result) && in_array(self::TYPE_INT, $expectedTypes);
        $isDoubleType = is_double($result) && in_array(self::TYPE_DOUBLE, $expectedTypes);
        $isStringType = is_string($result) && in_array(self::TYPE_STRING, $expectedTypes);
        $isArrayType = is_array($result) && in_array(self::TYPE_ARRAY, $expectedTypes);
        $isObjectType = is_object($result) && in_array(get_class($result), $expectedTypes);

        return $isNullType || $isBoolType || $isObjectType || $isArrayType || $isIntType || $isDoubleType || $isStringType;
    }
}
