<?php
namespace IvixLabs\RabbitmqBundle\Connection;

class ConnectionFactory
{

    private $settings;

    /**
     * @var ConnectionStorage[]
     */
    private $connectionStorages = [];

    /**
     * @var \SplObjectStorage
     */
    private $connections;

    /**
     * ConnectionFactory constructor.
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->connections = new \SplObjectStorage();
    }

    /**
     * @param $name
     * @return ConnectionStorage
     */
    public function getConnectionStorage($name = 'default')
    {
        if (!isset($this->connectionStorages[$name])) {
            if (!isset($this->settings['connections'][$name])) {
                throw new \LogicException('No settings for connection ' . $name);
            }

            $this->connectionStorages[$name] =
                new ConnectionStorage(
                    $name,
                    $this->settings['connections'][$name],
                    $this->settings['channels'],
                    $this->settings['exchanges'],
                    $this->settings['queues']
                );
        }

        return $this->connectionStorages[$name];
    }

    /**
     * @param \AMQPConnection $connection
     * @return ConnectionStorage
     */
    public function getConnectionStorageByConnection(\AMQPConnection $connection)
    {
        if (!isset($this->connections[$connection])) {
            $found = false;
            foreach ($this->connectionStorages as $connectionStorage) {
                if ($connectionStorage->getConnection() === $connection) {
                    $this->connections[$connection] = $connectionStorage;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new \LogicException('ConnectionStorage not found');
            }
        }

        return $this->connections[$connection];
    }

    /**
     * @return \AMQPQueue[]
     */
    public function getAllQueues()
    {
        $queues = [];
        foreach ($this->connectionStorages as $connectionStorage) {
            foreach ($connectionStorage->getAllQueues() as $queue) {
                $queues[] = $queue;
            }
        }

        return $queues;
    }
}