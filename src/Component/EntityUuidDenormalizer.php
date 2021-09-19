<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;

/**
 * Class EntityUuidDenormalizer
 */
class EntityUuidDenormalizer implements ContextAwareDenormalizerInterface
{
    const UUID_REGEX = '#\b[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}\b#';
    const CLASS_MAP = 'entity_denormalize_class_map';

    private ManagerRegistry $registry;


    /**
     * EntityUuidDenormalizer constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
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
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        $entityClasses = isset($context[self::CLASS_MAP]) ? array_keys($context[self::CLASS_MAP]) : [];

        return in_array($type, $entityClasses) && is_string($data) && 1 === preg_match(self::UUID_REGEX, $data);
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
     */
    public function denormalize($data, string $type, string $format = null, array $context = []): object
    {
        try {
            if (null === ($manager = $this->registry->getManagerForClass($type))) {
                throw new UnexpectedValueException(sprintf('Manager for class "%s" not found!', $type));
            }

            $repository = $manager->getRepository($type);
        } catch (Exception $e) {
            throw new UnexpectedValueException(sprintf('Repository for class "%s" not found!', $type), 0, $e);
        }

        $hydratingMethod = $context[self::CLASS_MAP][$type] ?? 'find';

        try {
            return $repository->{$hydratingMethod}($data);
        } catch (Exception $e) {
            throw new UnexpectedValueException(sprintf('Trying to call $em->%s(%s) failed!', $hydratingMethod, $data), 0, $e);
        }
    }
}
