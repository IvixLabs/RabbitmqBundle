<?php

namespace IvixLabs\RabbitmqBundle\DependencyInjection;

use Symfony\IvixLabs\Config\Definition\Builder\TreeBuilder;
use Symfony\IvixLabs\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ivixlabs_rabbitmq');

        $rootNode
            ->children()
            ->arrayNode('connections')
                ->prototype('array')
                    ->children()
                        ->scalarNode('host')->end()
                        ->scalarNode('port')->end()
                        ->scalarNode('user')->end()
                        ->scalarNode('password')->end()
                    ->end()
                ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
