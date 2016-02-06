<?php
namespace IvixLabs\RabbitmqBundle\Client;

use IvixLabs\RabbitmqBundle\Annotation;
use IvixLabs\RabbitmqBundle\Command\ExitException;
use IvixLabs\RabbitmqBundle\Command\RejectMessageException;
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

            $connection = $mainQueue->getConnection();
            $connectionStorage = $this->connectionFactory->getConnectionStorageByConnection($connection);

            $connectionName = $connectionStorage->getConnectionName();

            $channel = $mainQueue->getChannel();
            $channelName = $connectionStorage->getChannelName($channel);

            $exchangeName = $connectionStorage->getExchangeName($msg->getExchangeName());

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

            $key = $connectionName . '_' . $channelName . '_' . $exchangeName . '_';
            if (isset($this->taskClasses[$key])) {
                $consumers = array_merge($consumers, $this->taskClasses[$key]);
            }

            if (empty($consumers)) {
                $msg = 'Consumer not found: ';
                $msg .= implode(', ', [
                    'connection=' . $connectionName,
                    'channel=' . $channelName,
                    'exchange=' . $exchangeName,
                    'routingKey=' . $routingKey
                ]);
                throw new \LogicException($msg);
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
                    $mainQueue->nack($msg->getDeliveryTag());
                    $isAsk = false;
                    break;
                } catch (ExitException $e) {
                    $isContinue = false;
                }

            }

            if ($isAsk) {
                $mainQueue->ack($msg->getDeliveryTag());
            }

            return $isContinue;
        };

        $mainQueue->consume($callback);
    }

}