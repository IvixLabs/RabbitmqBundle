<?php
namespace IvixLabs\RabbitmqBundle\Client;

use IvixLabs\RabbitmqBundle\Connection\ConnectionFactory;
use IvixLabs\RabbitmqBundle\Message\MessageInterface;

class Producer
{

    /**
     * @var string
     */
    private $connectionName;

    /**
     * @var string
     */
    private $channelName;

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * @var \AMQPExchange
     */
    private $exchange;

    /**
     * Producer constructor.
     * @param $connectionName
     * @param $channelName
     * @param ConnectionFactory $connectionFactory
     */
    public function __construct($connectionName, $channelName, ConnectionFactory $connectionFactory)
    {
        $this->connectionName = $connectionName;
        $this->channelName = $channelName;
        $this->connectionFactory = $connectionFactory;
    }

    public function publish(MessageInterface $message)
    {
        $exchange = $this->getExchange($message->getExchange());
        $exchange->publish($message->toString(), $message->getRoutingKey());
    }

    /**
     * @param $realName
     * @return \AMQPExchange
     */
    private function getExchange($realName)
    {
        if ($this->exchange === null) {
            $connectionStorage = $this->connectionFactory->getConnectionStorage($this->connectionName);
            $name = $connectionStorage->getExchangeName($realName);
            $this->exchange = $connectionStorage->getExchange($name, $this->channelName);
        }

        return $this->exchange;
    }
}