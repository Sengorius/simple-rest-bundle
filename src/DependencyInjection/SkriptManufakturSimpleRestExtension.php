<?php

namespace SkriptManufaktur\SimpleRestBundle\DependencyInjection;

use Exception;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * Class SkriptManufakturSimpleRestExtension
 */
class SkriptManufakturSimpleRestExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // load the configuration
        $configuration = $this->processConfiguration(new Configuration(), $configs);
    }
}
