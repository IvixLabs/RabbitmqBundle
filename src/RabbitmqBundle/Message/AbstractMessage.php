<?php

namespace IvixLabs\RabbitmqBundle\Message;


abstract class AbstractMessage implements MessageInterface
{

    protected $exchange;

    protected $routingKey;

    protected $data;

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

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }


}