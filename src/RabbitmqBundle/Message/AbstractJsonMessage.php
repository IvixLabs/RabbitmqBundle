<?php
namespace IvixLabs\RabbitmqBundle\Message;

use IvixLabs\Common\Object\AbstractJsonObject;

abstract class AbstractJsonMessage extends AbstractJsonObject implements MessageInterface
{
    protected $exchangeName;

    protected $routingKey;

    public function __construct($exchangeName, $routingKey = null)
    {
        $this->exchangeName = $exchangeName;
        $this->routingKey = $routingKey;
    }

    /**
     * @return string
     */
    public function getExchangeName()
    {
        return $this->exchangeName;
    }

    /**
     * @return string
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }
}