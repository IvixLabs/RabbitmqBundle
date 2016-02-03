<?php
namespace IvixLabs\RabbitmqBundle\Connection;

class ConnectionFactory
{

    private $connectionSettings = [];

    /**
     * @var \AMQPConnection[]
     */
    private $connections = [];

    /**
     * @var \SplObjectStorage
     */
    private $channels;

    /**
     * @var \SplObjectStorage
     */
    private $exchanges;

    /**
     * ConnectionFactory constructor.
     */
    public function __construct()
    {
        $this->channels = new \SplObjectStorage();
        $this->exchanges = new \SplObjectStorage();
    }

    public function addConnectionSettings($name, $settings)
    {
        $this->connectionSettings[$name] = $settings;
    }

    /**
     * @param $name
     * @return \AMQPConnection
     */
    public function getConnection($name)
    {
        if (!isset($this->connections[$name])) {
            if (!isset($this->connectionSettings[$name])) {
                throw new \LogicException('No settings for connection ' . $name);
            }
            $settings = $this->connectionSettings[$name];

            $connection = new \AMQPConnection();
            $connection->setHost($settings['host']);
            $connection->setPort($settings['port']);
            $connection->setLogin($settings['user']);
            $connection->setPassword($settings['password']);

            $this->connections[$name] = $connection;

        }

        return $this->connections[$name];
    }

    /**
     * @param $connectionName
     * @param string $channelName
     * @return \AMQPChannel
     */
    public function getChannel($connectionName, $channelName = 'default')
    {
        $connection = $this->getConnection($connectionName);
        if (!isset($this->channels[$connection])) {
            $this->channels[$connection] = [];
        }

        $channels = $this->channels[$connection];
        if (!isset($channels[$channelName])) {
            $channel = new \AMQPChannel($connection);
            $channels[$channelName] = $channel;
        }

        return $channels[$channelName];
    }

    /**
     * @param $connectionName
     * @param string $channelName
     * @param string $exchangeName
     * @return \AMQPExchange
     */
    public function getExchange($connectionName, $channelName = null, $exchangeName = 'default')
    {
        if($channelName === null) {
            $channelRealName = 'default';
        } else {
            $channelRealName = $channelName;
        }

        $channel = $this->getChannel($connectionName, $channelRealName);
        if(!isset($this->exchanges[$channel])) {
            $this->exchanges[$channel] = [];
        }

        $exchanges = $this->exchanges[$channel];
        if(!isset($exchanges[$exchangeName])) {
            $exchange = new \AMQPExchange($channel);

            $exchanges[$exchangeName] = $exchange;
        }

        return $exchanges[$exchangeName];
    }

    function __destruct()
    {
        foreach ($this->connections as $conn) {
            foreach ($conn->channels as $channel) {
                $channel->close();
            }
            $conn->close();
        }
    }
}