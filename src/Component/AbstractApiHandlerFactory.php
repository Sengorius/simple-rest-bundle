<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use SkriptManufaktur\SimpleRestBundle\Exception\ApiProcessException;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractApiHandlerFactory
{
    protected ValidatorInterface $validator;
    protected SerializerInterface&NormalizerInterface&DenormalizerInterface&EncoderInterface&DecoderInterface $serializer;
    protected ApiBusWrapper $apiBus;


    public function setServices(ValidatorInterface $validator, SerializerInterface&NormalizerInterface&DenormalizerInterface&EncoderInterface&DecoderInterface $serializer, ApiBusWrapper $apiBus): void
    {
        $this->validator = $validator;
        $this->serializer = $serializer;
        $this->apiBus = $apiBus;
    }

    /**
     * Simple mapper from entity to array
     *
     * @param object   $entity
     * @param string[] $groups
     *
     * @return array<mixed>|\ArrayObject<int, null>|bool|float|int|string|null
     *
     * @throws ApiProcessException
     */
    protected function normalize(object $entity, array $groups = []): array|\ArrayObject|bool|float|int|string|null
    {
        $context = [
            AbstractNormalizer::GROUPS => $groups,
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, string|null $format, array $context): string|int|null {
                if (method_exists($object, 'getUuid')) {
                    return $object->getUuid();
                }

                if (method_exists($object, 'getId')) {
                    return $object->getId();
                }

                return null;
            },
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
     * @param array<mixed> $data
     * @param string       $entityClass
     * @param object|null  $populated
     * @param string[]     $groups
     * @param string[]     $entityClassMap
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
