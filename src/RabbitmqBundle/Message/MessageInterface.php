<?php
namespace IvixLabs\RabbitmqBundle\Message;

use IvixLabs\Common\Object\StringableInterface;

interface MessageInterface extends StringableInterface
{

    /**
     * @return string
     */
    public function getExchangeName();

    /**
     * @return string
     */
    public function getRoutingKey();
}