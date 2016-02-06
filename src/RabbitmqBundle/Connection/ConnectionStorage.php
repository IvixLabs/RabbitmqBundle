<?php
namespace IvixLabs\RabbitmqBundle\Connection;


class ConnectionStorage
{

    /**
     * @var \AMQPConnection
     */
    private $connection;

    /**
     * @var \AMQPChannel[]
     */
    private $channels = [];

    /**
     * @var \SplObjectStorage
     */
    private $objectChannels;

    /**
     * @var \AMQPQueue[]
     */
    private $queues = [];

    /**
     * @var \AMQPExchange[]
     */
    private $exchanges = [];

    private $connectionName;

    /**
     * @var array
     */
    private $connectionSettings;

    /**
     * @var array
     */
    private $channelsSettings;

    /**
     * @var array
     */
    private $exchangesSettings;

    /**
     * @var array
     */
    private $queuesSettings;

    public function __construct($connectionName, array $connectionSettings,
                                array $channelsSettings, array $exchangesSettings, array $queuesSettings)
    {
        $this->channelsSettings = $channelsSettings;
        $this->exchangesSettings = $exchangesSettings;
        $this->queuesSettings = $queuesSettings;
        $this->connectionSettings = $connectionSettings;
        $this->connectionName = $connectionName;
        $this->objectChannels = new \SplObjectStorage();
    }

    /**
     * @return \AMQPConnection
     */
    public function getConnection()
    {
        if ($this->connection === null) {
            $settings = $this->connectionSettings;

            $connection = new \AMQPConnection();
            $connection->setHost($settings['host']);
            $connection->setPort($settings['port']);
            $connection->setLogin($settings['user']);
            $connection->setPassword($settings['password']);
            $connection->connect();

            $this->connection = $connection;
        }

        return $this->connection;
    }

    /**
     * @param $name
     * @return \AMQPChannel
     */
    public function getChannel($name = 'default')
    {
        if (!isset($this->channels[$name])) {
            if (!isset($this->channelsSettings[$name])) {
                throw new \LogicException('No settings for channel ' . $name);
            }

            $connection = $this->getConnection();
            $channel = new \AMQPChannel($connection);

            $this->channels[$name] = $channel;
            $this->objectChannels[$channel] = $name;
        }
        return $this->channels[$name];
    }

    /**
     * @param \AMQPChannel $channel
     * @return string
     */
    public function getChannelName(\AMQPChannel $channel)
    {
        if (!isset($this->objectChannels[$channel])) {
            throw new \LogicException('Channel name not found');
        }

        return $this->objectChannels[$channel];
    }

    /**
     * @param $name
     * @param string $channelName
     * @return \AMQPQueue
     */
    public function getQueue($name = 'default', $channelName = 'default')
    {
        $key = $channelName . '_' . $name;

        if (!isset($this->queues[$key])) {
            $channel = $this->getChannel($channelName);

            if (!isset($this->queuesSettings[$name])) {
                throw new \LogicException('No settings for queue ' . $name);
            }

            $settings = $this->queuesSettings[$name];
            $queue = new \AMQPQueue($channel);

            if($settings['name'] !== null) {
                $queue->setName($settings['name']);
            }

            $queueFlags = AMQP_NOPARAM;
            if ($settings['durable']) {
                $queueFlags |= AMQP_DURABLE;
            }
            if ($settings['exclusive']) {
                $queueFlags |= AMQP_EXCLUSIVE;
            }
            if ($settings['autoDelete']) {
                $queueFlags |= AMQP_AUTODELETE;
            }
            if ($settings['passive']) {
                $queueFlags |= AMQP_PASSIVE;
            }

            $queue->setFlags($queueFlags);
            $queue->declareQueue();

            $this->queuesSettings[$name]['name'] = $queue->getName();
            $this->queues[$key] = $queue;
        }

        return $this->queues[$key];
    }

    /**
     * @param $realName
     * @return int|string
     */
    public function getQueueName($realName)
    {
        static $cache = [];
        if (isset($cache[$realName])) {
            return $cache[$realName];
        }

        foreach ($this->queuesSettings as $queueName => $queueSettings) {
            if ($queueSettings['name'] == $realName) {
                $cache[$realName] = $queueName;
                return $queueName;
            }
        }

        throw new \LogicException('Queue name not found for name:' . $realName);
    }

    public function getExchange($name = 'default', $channelName = 'default')
    {
        $key = $channelName . '_' . $name;

        if (!isset($this->exchanges[$key])) {
            $channel = $this->getChannel($channelName);

            if (!isset($this->exchangesSettings[$name])) {
                throw new \LogicException('No settings for exchange ' . $name);
            }

            $settings = $this->exchangesSettings[$name];
            $exchange = new \AMQPExchange($channel);
            $exchange->setName($settings['name']);

            if (strpos($settings['name'], 'amq.') !== 0) {
                $queueFlags = AMQP_NOPARAM;
                if ($settings['durable']) {
                    $queueFlags |= AMQP_DURABLE;
                }
                if ($settings['passive']) {
                    $queueFlags |= AMQP_PASSIVE;
                }
                if ($settings['autoDelete']) {
                    $queueFlags |= AMQP_AUTODELETE;
                }
                $exchange->setType($this->getExchangeType($settings['type']));
                $exchange->setFlags($queueFlags);
                $exchange->declareExchange();
            }

            $this->exchanges[$key] = $exchange;
        }

        return $this->exchanges[$key];
    }

    /**
     * @param $realName
     * @return int|string
     */
    public function getExchangeName($realName)
    {
        static $cache = [];
        if (isset($cache[$realName])) {
            return $cache[$realName];
        }

        foreach ($this->exchangesSettings as $exchangeName => $exchangeSettings) {
            if ($exchangeSettings['name'] == $realName) {
                $cache[$realName] = $exchangeName;
                return $exchangeName;
            }
        }

        throw new \LogicException('Exchange name not found for name:' . $realName);
    }

    private function getExchangeType($string)
    {
        static $type = [
            'direct' => AMQP_EX_TYPE_DIRECT,
            'fanout' => AMQP_EX_TYPE_FANOUT,
            'topic' => AMQP_EX_TYPE_TOPIC,
            'headers' => AMQP_EX_TYPE_HEADERS,
        ];
        if (!isset($type[$string])) {
            throw new \LogicException('Wrong exchange type ' . $string);
        }

        return $type[$string];
    }

    /**
     * @return \AMQPQueue[]
     */
    public function getAllQueues()
    {
        return $this->queues;
    }

    /**
     * @return mixed
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }
}