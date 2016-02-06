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
        $connectionStorage = $this->connectionFactory->getConnectionStorage($this->connectionName);
        $exchange = $connectionStorage->getExchange($message->getExchangeName(), $this->channelName);
        $exchange->publish($message->toString(), $message->getRoutingKey());
    }
}