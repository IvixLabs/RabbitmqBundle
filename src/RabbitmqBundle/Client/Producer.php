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
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * @var \AMQPExchange
     */
    private $exchange;

    /**
     * Producer constructor.
     * @param string $connectionName
     * @param ConnectionFactory $connectionFactory
     */
    public function __construct($connectionName, ConnectionFactory $connectionFactory)
    {
        $this->connectionName = $connectionName;
        $this->connectionFactory = $connectionFactory;
    }

    public function publish(MessageInterface $message)
    {
        $exchange= $this->getExchange($message->getExchange());
        $exchange->publish($message->toString(), $message->getQueue());
    }

    /**
     * @param $name
     * @return \AMQPExchange
     */
    private function getExchange($name)
    {
        if ($this->exchange === null) {
            $this->exchange = $this->connectionFactory->getExchange($this->connectionName, null, $name);
        }

        return $this->exchange;
    }
}