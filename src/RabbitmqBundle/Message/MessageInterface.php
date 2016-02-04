<?php

namespace IvixLabs\RabbitmqBundle\Message;


interface MessageInterface
{

    /**
     * @return string
     */
    public function getExchange();

    /**
     * @return string
     */
    public function getRoutingKey();

    /**
     * @param $string
     */
    public function fromString($string);

    /**
     * @return string
     */
    public function toString();
}