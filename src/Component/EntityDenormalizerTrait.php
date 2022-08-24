<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use Doctrine\Persistence\ObjectRepository;
use Exception;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

trait EntityDenormalizerTrait
{
    private array $repositories;

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
}
