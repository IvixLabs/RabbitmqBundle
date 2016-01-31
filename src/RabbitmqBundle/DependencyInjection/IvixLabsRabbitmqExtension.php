<?php
namespace IvixLabs\RabbitmqBundle\DependencyInjection;

use IvixLabs\RabbitmqBundle\Client\Producer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class IvixLabsRabbitmqExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $connectionFactoryDefinition = $container->getDefinition('ivixlabs.rabbitmq.factory.connection');

        $producers = [];
        foreach ($config['connections'] as $connectionName => $settings) {
            $connectionFactoryDefinition->addMethodCall('addConnectionSettings', array($connectionName, $settings));

            $definition = new Definition(Producer::class, [$connectionName, $connectionFactoryDefinition]);
            $id = 'ivixlabs.rabbitmq.producer.' . $connectionName;
            $producers[$id] = $definition;

        }
        $container->addDefinitions($producers);

    }
}
