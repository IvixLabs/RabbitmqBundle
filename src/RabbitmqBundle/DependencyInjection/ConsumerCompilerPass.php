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
        $serviceId = 'ivixlabs.rabbitmq.manager.consumer_worker';


        if (!$container->hasDefinition($serviceId)) {
            return;
        }

        $definition = $container->getDefinition($serviceId);

        $tag = 'ivixlabs.rabbitmq.consumer_worker';

        $services = $container->findTaggedServiceIds($tag);
        foreach ($services as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                if (isset($attributes['consumer_worker_name'])) {
                    $name = $attributes['consumer_worker_name'];
                } else {
                    $name = $id;
                }
                $definition->addMethodCall('addConsumerWorker', array(new Reference($id), $name));
            }
        }
    }
}