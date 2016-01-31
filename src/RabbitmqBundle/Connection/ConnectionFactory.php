<?php
namespace IvixLabs\RabbitmqBundle\Connection;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class ConnectionFactory
{

    private $connectionSettings = [];

    /**
     * @var AMQPStreamConnection[]
     */
    private $connections = [];

    public function addConnectionSettings($name, $settings)
    {
        $this->connectionSettings[$name] = $settings;
    }

    /**
     * @param $name
     * @return AMQPStreamConnection
     */
    public function getConnection($name)
    {
        if (!isset($this->connections[$name])) {
            if (!isset($this->connectionSettings[$name])) {
                throw new \LogicException('No settings for connection ' . $name);
            }
            $settings = $this->connectionSettings[$name];

            $connection = new AMQPStreamConnection(
                $settings['host'],
                $settings['port'],
                $settings['user'],
                $settings['password']
            );

            $this->connections[$name] = $connection;

        }

        return $this->connections[$name];
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