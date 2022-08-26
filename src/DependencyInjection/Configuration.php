<?php

namespace SkriptManufaktur\SimpleRestBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('skript_manufaktur_simple_rest');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $tree->getRootNode();

        /** @phpstan-ignore-next-line */
        $rootNode
            ->children()
                ->arrayNode('firewall_names')
                    ->info('The names of all firewalls (security.yaml) that will be used with the simple REST API')
                    ->beforeNormalization()
                        ->castToArray()
                    ->end()
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('default_requesting_origin')
                    ->info('Will be added as "_requesting_server" attribute to request object, if no HTTP_ORIGIN was found')
                    ->defaultValue('localhost')
                ->end()
                ->booleanNode('granting_middleware_throws')
                    ->info('Shall the middleware throw exceptions or just add grants to the stamps?')
                    ->defaultTrue()
                ->end()
            ->end()
        ;

        return $tree;
    }
}
