<?php
namespace IvixLabs\RabbitmqBundle\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ConsumerConnection
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $iterations = 1000;
}