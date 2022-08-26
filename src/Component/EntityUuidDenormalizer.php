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

class EntityUuidDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use EntityDenormalizerTrait;

    const UUID_REGEX = '#\b[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}\b#';
    const CLASS_MAP = 'entity_uuid_denormalize_class_map';
    const PREVENT = 'entity_uuid_denormalize_prevent_recursion';
    const KEY = 'uuid';

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
        $entityClasses = isset($context[self::CLASS_MAP]) ? array_keys($context[self::CLASS_MAP]) : [];
        $matchesClass = in_array($type, $entityClasses);
        $preventRecursion = isset($context[self::PREVENT]) && true === $context[self::PREVENT];

        return $matchesClass && !$preventRecursion && ($this->isDataAnUuid($data) || $this->isDataAnArray($data));
    }

    /**
     * Denormalizes UUID back into an object of the given class.
     *
     * @param mixed       $data    Data to restore
     * @param string      $type    The expected class to instantiate
     * @param string|null $format  Format the given data was extracted from
     * @param array       $context Options available to the denormalizer
     *
     * @return object
     *
     * @throws UnexpectedValueException Occurs when the item cannot be hydrated with the given data
     * @throws ExceptionInterface
     */
    public function denormalize($data, string $type, $format = null, array $context = []): object
    {
        $repository = $this->getRepository($type);
        $entityId = $this->getUuidFromData($data);
        $hydratingMethod = $context[self::CLASS_MAP][$type] ?? 'find';

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
            throw new UnexpectedValueException(sprintf('Trying to call $em->%s(%s) failed!', $hydratingMethod, $data), 0, $e);
        }
    }

    /**
     * @param mixed $data
     *
     * @return bool
     */
    private function isDataAnUuid($data): bool
    {
        return is_string($data) && 1 === preg_match(self::UUID_REGEX, $data);
    }

    /**
     * @param mixed $data
     *
     * @return bool
     */
    private function isDataAnArray($data): bool
    {
        $isArrayHasId = is_array($data) && array_key_exists(self::KEY, $data);

        return $isArrayHasId && (is_null($data[self::KEY]) || $this->isDataAnUuid($data[self::KEY]));
    }

    /**
     * @param mixed $data
     *
     * @return string|null
     */
    private function getUuidFromData($data): ?string
    {
        if ($this->isDataAnUuid($data)) {
            return $data;
        }

        return $data[self::KEY];
    }
}
