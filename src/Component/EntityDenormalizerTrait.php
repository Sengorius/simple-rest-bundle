<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use Doctrine\Persistence\ObjectRepository;
use Exception;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use function class_exists;
use function is_int;
use function is_string;
use function sprintf;

trait EntityDenormalizerTrait
{
    private string $defaultHydratingMethod = 'find';
    private array $repositories;
    private array $normalizedClasses = [];


    /**
     * @param string $type
     *
     * @return ObjectRepository
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
            return $this->repositories[$type] = $manager->getRepository($type);
        } catch (Exception $e) {
            throw new UnexpectedValueException(sprintf('Repository for class "%s" not found!', $type), 0, $e);
        }
    }

    /**
     * In case we get an array like [EntityOne::class, EntityTwo:class => 'getOne'],
     * keys and values are mixed and need to be normalized
     *
     * @param array $classMap
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

            if (!is_string($method)) {
                throw new UnexpectedValueException(sprintf('Got hydrating method "%s" which is not valid!', $method));
            }

            $this->normalizedClasses[$class] = $method;
        }
    }
}
