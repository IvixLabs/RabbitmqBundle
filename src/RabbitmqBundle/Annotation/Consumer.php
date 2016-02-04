<?php
namespace IvixLabs\RabbitmqBundle\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Consumer
{
    /**
     * @var string
     */
    public $queueName = 'default';

    /**
     * @var string
     */
    public $exchangeName = 'default';

    /**
     * @var string
     */
    public $channelName = 'default';

    /**
     * @var string
     */
    public $connectionName = 'default';

    /**
     * @var string
     */
    public $routingKey = null;
}