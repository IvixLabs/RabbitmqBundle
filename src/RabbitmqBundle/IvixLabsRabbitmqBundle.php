<?php
namespace IvixLabs\RabbitmqBundle;

use IvixLabs\RabbitmqBundle\DependencyInjection\ConsumerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class IvixLabsRabbitmqBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new ConsumerCompilerPass());
    }

}
