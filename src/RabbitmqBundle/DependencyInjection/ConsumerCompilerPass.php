<?php
namespace IvixLabs\RabbitmqBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConsumerCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $serviceId = 'ivixlabs.rabbitmq.manager.consumer';


        if (!$container->hasDefinition($serviceId)) {
            return;
        }

        $definition = $container->getDefinition($serviceId);

        $tag = 'ivixlabs.rabbitmq.worker';

        $services = $container->findTaggedServiceIds($tag);
        foreach ($services as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall('addConsumerService', array(new Reference($id), $id));
            }
        }
    }


}