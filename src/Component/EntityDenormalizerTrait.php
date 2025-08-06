<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use Doctrine\Persistence\ObjectRepository;
use Exception;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use function class_exists;
use function is_int;
use function is_string;
use function sprintf;

/** @template T of object */
trait EntityDenormalizerTrait
{
    private string $defaultHydratingMethod = 'find';

    /** @var array<string, ObjectRepository<T>> */
    private array $repositories;

    /** @var array<string, string> */
    private array $normalizedClasses = [];


    /**
     * @param class-string $type
     *
     * @return ObjectRepository<T>
     */
    private function getRepository(string $type): ObjectRepository
    {
        if (isset($this->repositories[$type])) {
            return $this->repositories[$type];
        }

        if (null === ($manager = $this->registry->getManagerForClass($type))) {
            throw new UnexpectedValueException(sprintf('Manager for class "%s" not found!', $type));
        }

        try {
            return $this->repositories[$type] = $manager->getRepository($type); // @phpstan-ignore-line
        } catch (Exception $e) {
            throw new UnexpectedValueException(sprintf('Repository for class "%s" not found!', $type), 0, $e);
        }
    }

    /**
     * In case we get an array like [EntityOne::class, EntityTwo:class => 'getOne'],
     * keys and values are mixed and need to be normalized
     *
     * @param array<string|int, string> $classMap
     */
    private function normalizeClassMap(array $classMap): void
    {
        $this->normalizedClasses = [];

        foreach ($classMap as $class => $method) {
            if (is_int($class)) {
                if (empty($method) || !class_exists($method, false)) {
                    throw new UnexpectedValueException(sprintf('Got "%s" which is not a valid class name!', $method));
                }

                $this->normalizedClasses[$method] = $this->defaultHydratingMethod;
                continue;
            }

            if (empty($class) || !class_exists($class, false)) {
                throw new UnexpectedValueException(sprintf('Got "%s" which is not a valid class name!', $class));
            }

            if (!is_string($method)) { // @phpstan-ignore-line
                throw new UnexpectedValueException(sprintf('Got hydrating method "%s" which is not valid!', $method));
            }

            $this->normalizedClasses[$class] = $method;
        }
    }
}
