<?php
namespace IvixLabs\RabbitmqBundle\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Consumer
{
    /**
     * @var array
     */
    public $exchange;


    /**
     * @var array
     */
    public $queue;

    /**
     * @var bool
     */
    public $ack = true;
}