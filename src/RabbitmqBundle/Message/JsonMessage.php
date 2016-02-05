<?php
namespace IvixLabs\RabbitmqBundle\Message;

use IvixLabs\Common\Object\AbstractJsonObject;

abstract class JsonMessage extends AbstractJsonObject implements MessageInterface
{
    protected $exchange;

    protected $routingKey;

    public function __construct($exchange, $routingKey = null)
    {
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
    }

    /**
     * @return string
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @return string
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }
}