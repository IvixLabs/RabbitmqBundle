<?php
namespace IvixLabs\RabbitmqBundle\Message;

use IvixLabs\Common\Object\AbstractJsonObject;

class MessageWrapper extends AbstractJsonObject
{
    protected $exchangeName;

    protected $routingKey;

    protected $objectString;

    public function __construct(MessageInterface $message)
    {
        $this->exchangeName = $message->getExchangeName();
        $this->routingKey = $message->getRoutingKey();
        $this->objectString = $message->toString();
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

    /**
     * @return mixed
     */
    public function getObjectString()
    {
        return $this->objectString;
    }
}