<?php
namespace IvixLabs\RabbitmqBundle\Client;

use IvixLabs\RabbitmqBundle\Connection\ConnectionFactory;
use IvixLabs\RabbitmqBundle\Message\MessageInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

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
     * @var AMQPChannel
     */
    private $channel;

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
        $channel = $this->getChannel();
        $msg = new AMQPMessage($message->toString());
        $channel->basic_publish($msg, $message->getExchange(), $message->getQueue());
    }

    public function add(MessageInterface $message)
    {
        $channel = $this->getChannel();
        $msg = new AMQPMessage($message->toString());
        $channel->batch_basic_publish($msg, $message->getExchange(), $message->getQueue());
    }

    public function flush() {
        $channel = $this->getChannel();
        $channel->publish_batch();
    }


    private function getChannel()
    {
        if ($this->channel === null) {
            $connection = $this->connectionFactory->getConnection($this->connectionName);
            $this->channel = $connection->channel();
        }

        return $this->channel;
    }
}