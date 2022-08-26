<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use SkriptManufaktur\SimpleRestBundle\Exception\ApiProcessException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractApiHandlerFactory
{
    protected ValidatorInterface $validator;
    protected Serializer $serializer;
    protected ApiBusWrapper $apiBus;
    protected ApiFilterService $apiFilter;


    public function setServices(ValidatorInterface $validator, Serializer $serializer, ApiBusWrapper $apiBus, ApiFilterService $apiFilter): void
    {
        $this->validator = $validator;
        $this->serializer = $serializer;
        $this->apiBus = $apiBus;
        $this->apiFilter = $apiFilter;
    }

    /**
     * Simple mapper from entity to array
     *
     * @param object   $entity
     * @param string[] $groups
     *
     * @return array
     *
     * @throws ApiProcessException
     */
    protected function normalize(object $entity, array $groups = []): array
    {
        $context = [
            AbstractNormalizer::GROUPS => $groups,
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => fn ($object, $format, $context) => $object->getId(),
        ];

        try {
            return $this->serializer->normalize($entity, null, $context);
        } catch (ExceptionInterface $e) {
            throw new ApiProcessException($e->getMessage(), $e);
        }
    }

    /**
     * Simple mapper from array to entity (by class)
     *
     * @param array       $data
     * @param string      $entityClass
     * @param object|null $populated
     * @param string[]    $groups
     * @param string[]    $entityClassMap
     *
     * @return object
     *
     */
    protected function denormalize(array $data, string $entityClass, object|null $populated = null, array $groups = [], array $entityClassMap = []): object
    {
        $context = [
            AbstractNormalizer::GROUPS => $groups,
            AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
            AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE => true,
        ];

        if (null !== $populated) {
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $populated;
        }

        if (!empty($entityClassMap)) {
            $context[EntityIdDenormalizer::CLASS_MAP] = $entityClassMap;
            $context[EntityUuidDenormalizer::CLASS_MAP] = $entityClassMap;
        }

        try {
            return $this->serializer->denormalize($data, $entityClass, null, $context);
        } catch (ExceptionInterface $e) {
            throw new ApiProcessException($e->getMessage(), $e);
        }
    }
}
