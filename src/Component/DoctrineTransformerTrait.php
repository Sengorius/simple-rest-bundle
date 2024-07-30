<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use function get_class;
use function is_object;
use function ltrim;
use function strrpos;
use function substr;

trait DoctrineTransformerTrait
{
    /**
     * Checks if doctrine does not manage data automatically.
     *
     * @param EntityManagerInterface $entityManager
     * @param object                 $data
     *
     * @return bool
     */
    protected function isDeferredExplicit(EntityManagerInterface $entityManager, object $data): bool
    {
        $realClassName = $this->getRealClassName($data);
        $classMetadata = $entityManager->getClassMetadata($realClassName);

        if ($classMetadata instanceof ClassMetadata && method_exists($classMetadata, 'isChangeTrackingDeferredExplicit')) {
            return $classMetadata->isChangeTrackingDeferredExplicit();
        }

        return false;
    }

    /**
     * Get the real class name of a class name that could be a proxy.
     *
     * @param object|string $className
     *
     * @return string
     */
    protected function getRealClassName(object|string $className): string
    {
        if (is_object($className)) {
            $className = get_class($className);
        }

        $positionCg = strrpos($className, '\\__CG__\\');
        $positionPm = strrpos($className, '\\__PM__\\');

        // __CG__: Doctrine Common Marker for Proxy (ODM < 2.0 and ORM < 3.0)
        // __PM__: Ocramius Proxy Manager (ODM >= 2.0)
        if (false === $positionCg && false === $positionPm) {
            return $className;
        }

        if (false !== $positionCg) {
            return substr($className, $positionCg + 8);
        }

        $className = ltrim($className, '\\');

        return substr(
            $className,
            8 + $positionPm,
            strrpos($className, '\\') - ($positionPm + 8)
        );
    }
}
