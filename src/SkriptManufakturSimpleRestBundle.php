<?php

namespace SkriptManufaktur\SimpleRestBundle;

use SkriptManufaktur\SimpleRestBundle\DependencyInjection\SkriptManufakturSimpleRestExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class SkriptManufakturSimpleRestBundle
 */
class SkriptManufakturSimpleRestBundle extends Bundle
{
    /**
     * @var SkriptManufakturSimpleRestExtension|null
     */
    protected $extension = null;


    /**
     * @return ExtensionInterface
     */
    public function getContainerExtension(): ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new SkriptManufakturSimpleRestExtension();
        }

        return $this->extension;
    }
}
