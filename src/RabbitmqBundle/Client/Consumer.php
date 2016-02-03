<?php
namespace IvixLabs\RabbitmqBundle\Client;

use IvixLabs\RabbitmqBundle\Annotation;
use IvixLabs\RabbitmqBundle\Connection\ConnectionFactory;
use IvixLabs\RabbitmqBundle\Message\MessageInterface;
use Doctrine\Common\Annotations\AnnotationReader;

class Consumer
{

    private $consumer;

    private $connectionName;

    /**
     * @var \SplObjectStorage
     */
    private $consumerSettings;

    private $taskClasses = [];

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    public function __construct($consumer, ConnectionFactory $connectionFactory)
    {
        $this->consumerSettings = new \SplObjectStorage();

        $this->connectionFactory = $connectionFactory;
        $this->consumer = $consumer;

        $className = get_class($consumer);
        $reflectionClass = new \ReflectionClass($className);
        $reader = new AnnotationReader();

        $classAnnotations = $reader->getClassAnnotations($reflectionClass);

        foreach ($classAnnotations as $annotation) {
            if ($annotation instanceof Annotation\ConsumerConnection) {
                $this->connectionName = $annotation->name;
                break;
            }
        }

        $methods = $reflectionClass->getMethods();
        foreach ($methods as $method) {
            $methodAnnotations = $reader->getMethodAnnotations($method);
            $parameters = $method->getParameters();
            $taskClass = false;
            if (!empty($parameters)) {
                $taskClass = $parameters[0]->getClass()->getName();
            }
            foreach ($methodAnnotations AS $annotation) {
                if ($annotation instanceof Annotation\Consumer) {
                    $this->consumerSettings[$annotation] = $method;
                    $id = $annotation->exchange['name'] . '_' . $annotation->queue['name'];
                    $this->taskClasses[$id] = [$taskClass, $annotation];
                }
            }
        }
    }


    public function execute()
    {
        /**
         * @var \ReflectionMethod $method
         * @var Consumer $consumer
         */
        foreach ($this->consumerSettings as $consumer) {
            $method = $this->consumerSettings[$consumer];


            if (isset($consumer->queue['durable'])) {
                $queueDurable = $consumer->queue['durable'];
            } else {
                $queueDurable = false;
            }

            if (isset($consumer->queue['exclusive'])) {
                $queueExclusive = $consumer->queue['exclusive'];
            } else {
                $queueExclusive = false;
            }

            if (isset($consumer->queue['autoDelete'])) {
                $queueAutoDelete = $consumer->queue['autoDelete'];
            } else {
                $queueAutoDelete = false;
            }

            $channel = $this->connectionFactory->getChannel($this->connectionName);

            $queue = new \AMQPQueue($channel);
            $queue->setName($consumer->queue['name']);
            $queue->setFlags(AMQP_DURABLE | AMQP_EXCLUSIVE | AMQP_AUTODELETE | AMQP_PASSIVE);
            $queue->setArgument(AMQP_DURABLE, $queueDurable);
            $queue->setArgument(AMQP_EXCLUSIVE, $queueExclusive);
            $queue->setArgument(AMQP_AUTODELETE, $queueAutoDelete);
            $queue->setArgument(AMQP_PASSIVE, false);
            $queue->
            $queue->declareQueue();

            if ($consumer->exchange !== null) {
                if (isset($consumer->exchange['durable'])) {
                    $exchangeDurable = $consumer->exchange['durable'];
                } else {
                    $exchangeDurable = false;
                }

                if (isset($consumer->exchange['autoDelete'])) {
                    $exchangeAutoDelete = $consumer->exchange['autoDelete'];
                } else {
                    $exchangeAutoDelete = false;
                }

                $exchangeName = $consumer->exchange['name'];

                $exchange = $this->connectionFactory->getExchange($this->connectionName, null, $exchangeName);
                $exchange->setFlags(AMQP_DURABLE | AMQP_AUTODELETE | AMQP_PASSIVE);
                $exchange->setArgument(AMQP_DURABLE, $exchangeDurable);
                $exchange->setArgument(AMQP_AUTODELETE, $exchangeAutoDelete);
                $exchange->setArgument(AMQP_PASSIVE, false);
                $exchange->setType($consumer->exchange['type']);
                $exchange->declareExchange();

                $exchange->bind($exchangeName);
                $queue->bind($exchangeName);
            }

            $channel = $this->connectionFactory->getChannel($this->connectionName);

            $channel->setPrefetchCount(1);

            $closure = $method->getClosure($this->consumer);
            $callback = function (\AMQPEnvelope $msg) use ($closure, $queue) {
                $id = $msg->getExchangeName() . '_' . $msg->getRoutingKey();
                /** @var Consumer $annotation */
                list($taskClass, $annotation) = $this->taskClasses[$id];

                if ($taskClass !== false) {
                    /** @var MessageInterface $task */
                    $task = new $taskClass();
                    $task->fromString($msg->getBody());
                    $result = $closure($task);
                } else {
                    $result = $closure();
                }

                if ($annotation->ack && $result) {
                    $queue->nack($msg->getDeliveryTag());
                }
            };

            $queue->consume($callback);
            //$channel->basic_consume($queueName, '', false, !$consumer->ack, false, false, $callback);
        }

        //$connnection = $this->connectionFactory->getConnection($this->connectionName);
        //while ($) {
        //    $connnection->;
        //}
        //$channel->close();
        //$connection->close();
    }

}