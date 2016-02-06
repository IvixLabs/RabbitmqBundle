<?php
namespace IvixLabs\RabbitmqBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

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
                ->arrayNode('channels')
                    ->prototype('array')
                        ->children()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('queues')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->defaultNull()->end()
                            ->booleanNode('durable')->defaultTrue()->end()
                            ->booleanNode('exclusive')->defaultFalse()->end()
                            ->booleanNode('autoDelete')->defaultTrue()->end()
                            ->booleanNode('passive')->defaultFalse()->end()
                            ->arrayNode('from_exchanges')->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('exchanges')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->defaultValue('amq.direct')->end()
                            ->scalarNode('type')->defaultValue('direct')->end()
                            ->booleanNode('durable')->defaultTrue()->end()
                            ->booleanNode('passive')->defaultFalse()->end()
                            ->booleanNode('autoDelete')->defaultTrue()->end()
                            ->arrayNode('to_exchanges')->useAttributeAsKey('name')
                                ->prototype('array')->
                                    prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('connections')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('host')->isRequired()->end()
                            ->scalarNode('port')->isRequired()->end()
                            ->scalarNode('user')->isRequired()->end()
                            ->scalarNode('password')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
