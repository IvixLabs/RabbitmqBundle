<?php
namespace IvixLabs\RabbitmqBundle\Client;

use IvixLabs\RabbitmqBundle\Annotation;
use IvixLabs\RabbitmqBundle\Connection\ConnectionFactory;
use IvixLabs\RabbitmqBundle\Message\MessageInterface;
use Doctrine\Common\Annotations\AnnotationReader;

class Consumer
{
    private $taskClasses = [];

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    public function __construct($consumerWorker, ConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;

        $className = get_class($consumerWorker);
        $reflectionClass = new \ReflectionClass($className);
        $reader = new AnnotationReader();

        $methods = $reflectionClass->getMethods();
        foreach ($methods as $method) {
            $methodAnnotations = $reader->getMethodAnnotations($method);
            foreach ($methodAnnotations AS $annotation) {
                if ($annotation instanceof Annotation\Consumer) {

                    $parameters = $method->getParameters();
                    $taskClassName = false;
                    if (!empty($parameters)) {
                        $taskClass = $parameters[0]->getClass();
                        $isMessage = $taskClass->implementsInterface('IvixLabs\RabbitmqBundle\Message\MessageInterface');
                        if (!$isMessage) {
                            throw new \InvalidArgumentException('Task must implmenet IvixLabs\RabbitmqBundle\Message\MessageInterface');
                        }
                        $taskClassName = $taskClass->getName();
                    }

                    $key = $this->getTaskClassKey($annotation);
                    if (isset($this->taskClasses[$key])) {
                        $msg = 'Consumer like it already registered: ';
                        $msg .= implode(', ', [
                            'connection=' . $annotation->connectionName,
                            'channel=' . $annotation->channelName,
                            'exchange=' . $annotation->exchangeName,
                            'queue=' . $annotation->queueName,
                            'routingKey=' . $annotation->routingKey
                        ]);
                        throw new \LogicException($msg);
                    }
                    $this->taskClasses[$key] = [
                        $taskClassName,
                        $method->getClosure($consumerWorker),
                        $annotation
                    ];
                }
            }
        }
    }

    private function getTaskClassKey(Annotation\Consumer $annotation)
    {
        return $annotation->connectionName . '_' . $annotation->channelName . '_'
        . $annotation->exchangeName . '_' . $annotation->queueName . '_' . $annotation->routingKey;
    }

    public function execute()
    {
        /**
         * @var \ReflectionMethod $method
         * @var Annotation\Consumer $annotation
         */
        foreach ($this->taskClasses as list($taskClass, $method, $annotation)) {

            $connectionStorage = $this->connectionFactory->getConnectionStorage($annotation->connectionName);
            $queue = $connectionStorage->getQueue($annotation->queueName);
            $exchange = $connectionStorage->getExchange($annotation->exchangeName);

            $queue->bind($exchange->getName(), $annotation->routingKey);
        }

        $callback = function (\AMQPEnvelope $msg, \AMQPQueue $queue) {

            $connection = $queue->getConnection();
            $connectionStorage = $this->connectionFactory->getConnectionStorageByConnection($connection);

            $connectionName = $connectionStorage->getConnectionName();

            $channel = $queue->getChannel();
            $channelName = $connectionStorage->getChannelName($channel);

            $exchangeName = $connectionStorage->getExchangeName($msg->getExchangeName());
            $queueName = $connectionStorage->getQueueName($queue->getName());

            $routingKey = $msg->getRoutingKey();

            $id = $connectionName . '_' . $channelName . '_' . $exchangeName . '_' .
                $queueName . '_' . $routingKey;
            if (!isset($this->taskClasses[$id])) {
                $id = $connectionName . '_' . $channelName . '_' . $exchangeName . '_' .
                    $queueName . '_';
            }

            if (isset($this->taskClasses[$id])) {
                $msg = 'Consumer not found: ';
                $msg .= implode(', ', [
                    'connection=' . $connectionName,
                    'channel=' . $channelName,
                    'exchange=' . $exchangeName,
                    'queue=' . $queueName,
                    'routingKey=' . $routingKey
                ]);
                throw new \LogicException($msg);
            }

            /** @var \Closure $method */
            /** @var MessageInterface $taskClass */
            list($taskClass, $method) = $this->taskClasses[$id];

            if ($taskClass !== false) {
                $task = $taskClass::createFromString($msg->getBody());
                $result = $method($task);
            } else {
                $result = $method();
            }

            if ($result) {
                $queue->ack($msg->getDeliveryTag());
            }
        };

        $queues = $this->connectionFactory->getAllQueues();

        $mainQueue = array_pop($queues);
        foreach ($queues as $queue) {
            $queue->consume();
        }

        $mainQueue->consume($callback);
    }

}