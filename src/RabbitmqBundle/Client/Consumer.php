<?php
namespace IvixLabs\RabbitmqBundle\Client;

use IvixLabs\RabbitmqBundle\Annotation;
use IvixLabs\RabbitmqBundle\Exception\ExitConsumerWorkerException;
use IvixLabs\RabbitmqBundle\Exception\RejectMessageException;
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

                    $key =
                        $annotation->connectionName . '_' .
                        $annotation->channelName . '_' .
                        $annotation->exchangeName . '_' .
                        $annotation->routingKey;

                    if (!isset($this->taskClasses[$key])) {
                        $this->taskClasses[$key] = [];
                    }
                    $this->taskClasses[$key][] = [
                        $taskClassName,
                        $method->getClosure($consumerWorker),
                        $annotation
                    ];
                }
            }
        }
    }

    public function execute()
    {
        /**
         * @var \ReflectionMethod $method
         * @var Annotation\Consumer $annotation
         */
        foreach ($this->taskClasses as $consumers) {
            foreach ($consumers as list($taskClass, $method, $annotation)) {

                $connectionStorage = $this->connectionFactory->getConnectionStorage($annotation->connectionName);
                $connectionStorage->getQueue($annotation->queueName);
                $connectionStorage->getExchange($annotation->exchangeName);
            }
        }

        $queues = $this->connectionFactory->getAllQueues();

        /** @var \AMQPQueue $mainQueue */
        $mainQueue = array_pop($queues);
        foreach ($queues as $queue) {
            $queue->consume();
        }

        $callback = function (\AMQPEnvelope $msg) use ($mainQueue) {

            $deliveryTag = $msg->getDeliveryTag();

            $connection = $mainQueue->getConnection();
            $connectionStorage = $this->connectionFactory->getConnectionStorageByConnection($connection);

            $connectionName = $connectionStorage->getConnectionName();

            $channel = $mainQueue->getChannel();
            $channelName = $connectionStorage->getChannelName($channel);

            $exchangeName = $msg->getExchangeName();
            $routingKey = $msg->getRoutingKey();

            $consumers = [];
            $key =
                $connectionName . '_' .
                $channelName . '_' .
                $exchangeName . '_' .
                $routingKey;
            if (isset($this->taskClasses[$key])) {
                $consumers = array_merge($consumers, $this->taskClasses[$key]);
            }

            $keyNull = $connectionName . '_' . $channelName . '_' . $exchangeName . '_';
            if ($keyNull !== $key && isset($this->taskClasses[$keyNull])) {
                $consumers = array_merge($consumers, $this->taskClasses[$keyNull]);
            }

            if (empty($consumers)) {
                $exceptionMessage = 'Consumer not found: ';
                $exceptionMessage .= implode(', ', [
                    'connection=' . $connectionName,
                    'channel=' . $channelName,
                    'exchange=' . $exchangeName,
                    'routingKey=' . $routingKey
                ]);
                throw new \LogicException($exceptionMessage);
            }

            $isAsk = true;
            $isContinue = true;
            foreach ($consumers as list($taskClass, $method)) {
                /** @var \Closure $method */
                /** @var MessageInterface $taskClass */

                try {
                    if ($taskClass !== false) {
                        $task = $taskClass::createFromString($msg->getBody());
                        $method($task);
                    } else {
                        $method();
                    }
                } catch (RejectMessageException $e) {
                    $mainQueue->nack($deliveryTag);
                    $isAsk = false;
                    break;
                } catch (ExitConsumerWorkerException $e) {
                    $isContinue = false;
                }
            }

            if ($isAsk) {
                $mainQueue->ack($deliveryTag);
            }

            return $isContinue;
        };

        $mainQueue->consume($callback);
    }

}