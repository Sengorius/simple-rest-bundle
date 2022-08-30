<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use function array_key_exists;
use function in_array;
use function is_array;
use function is_int;
use function is_null;

class EntityIdDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use EntityDenormalizerTrait;

    const CLASS_MAP = 'entity_id_denormalize_class_map';
    const PREVENT = 'entity_id_denormalize_prevent_recursion';
    const KEY = 'id';

    private ManagerRegistry $registry;
    private DenormalizerInterface $denormalizer;


    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function setDenormalizer(DenormalizerInterface $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    /**
     * Checks whether the given class is supported for denormalization by this normalizer.
     *
     * @param mixed       $data    Data to denormalize from
     * @param string      $type    The class to which the data should be denormalized
     * @param string|null $format  The format being deserialized from
     * @param array       $context options that denormalizers have access to
     *
     * @return bool
     */
    public function supportsDenormalization($data, string $type, $format = null, array $context = []): bool
    {
        if (!isset($context[self::CLASS_MAP]) || !is_array($context[self::CLASS_MAP]) || empty($context[self::CLASS_MAP])) {
            return false;
        }

        $this->normalizeClassMap($context[self::CLASS_MAP]);
        $matchesClass = in_array($type, array_keys($this->normalizedClasses), true);
        $preventRecursion = isset($context[self::PREVENT]) && true === $context[self::PREVENT];

        return $matchesClass && !$preventRecursion && ($this->isDataAnInteger($data) || $this->isDataAnArray($data));
    }

    /**
     * Denormalizes an ID back into an object of the given class.
     *
     * @param mixed       $data    Data to restore
     * @param string      $type    The expected class to instantiate
     * @param string|null $format  Format the given data was extracted from
     * @param array       $context Options available to the denormalizer
     *
     * @return object|null
     *
     * @throws UnexpectedValueException Occurs when the item cannot be hydrated with the given data
     * @throws ExceptionInterface
     */
    public function denormalize($data, string $type, $format = null, array $context = []): ?object
    {
        $repository = $this->getRepository($type);
        $entityId = $this->getIdFromData($data);
        $hydratingMethod = $this->normalizedClasses[$type] ?? $this->defaultHydratingMethod;

        try {
            $result = $repository->{$hydratingMethod}($entityId);

            // if data is an array, update all given attributes
            // it shall be done only once to prevent recursion, so we have to add this info to the context
            if ($this->isDataAnArray($data)) {
                unset($data[self::KEY]);
                $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $result;
                $context[self::PREVENT] = true;
                $this->denormalizer->denormalize($data, $type, $format, $context);
            }

            return $result;
        } catch (Exception $e) {
            throw new UnexpectedValueException(
                sprintf('Trying to call %s::%s(%s) failed!', get_class($repository), $hydratingMethod, $entityId),
                0,
                $e
            );
        }
    }

    /**
     * @param mixed $data
     *
     * @return bool
     */
    private function isDataAnInteger($data): bool
    {
        return is_int($data) && ((int) $data > 0);
    }

    /**
     * @param mixed $data
     *
     * @return bool
     */
    private function isDataAnArray($data): bool
    {
        $isArrayHasId = is_array($data) && array_key_exists(self::KEY, $data);

        return $isArrayHasId && (is_null($data[self::KEY]) || $this->isDataAnInteger($data[self::KEY]));
    }

    /**
     * @param mixed $data
     *
     * @return int|null
     */
    private function getIdFromData($data): ?int
    {
        if ($this->isDataAnInteger($data)) {
            return $data;
        }

        return $data[self::KEY];
    }
}
