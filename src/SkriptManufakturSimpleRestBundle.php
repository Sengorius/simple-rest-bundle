<?php

namespace SkriptManufaktur\SimpleRestBundle;

use SkriptManufaktur\SimpleRestBundle\DependencyInjection\SkriptManufakturSimpleRestExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SkriptManufakturSimpleRestBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new SkriptManufakturSimpleRestExtension();
        }

        return $this->extension;
    }
}
