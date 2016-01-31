<?php

namespace IvixLabs\RabbitmqBundle\Message;


abstract class AbstractMessage implements MessageInterface
{

    protected $exchange;

    protected $queue;

    protected $data;

    public function __construct($exchange, $queue)
    {
        $this->exchange = $exchange;
        $this->queue = $queue;
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
    public function getQueue()
    {
        return $this->queue;
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