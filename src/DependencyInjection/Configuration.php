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
                ->arrayNode('path_prefixes')
                    ->info('URL path prefixes that the API response listener should also handle when no firewall context is available yet (e.g. router-level 404s). Each entry is a literal prefix, matched with str_starts_with().')
                    ->beforeNormalization()
                        ->castToArray()
                    ->end()
                    ->scalarPrototype()->end()
                    ->defaultValue([])
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
