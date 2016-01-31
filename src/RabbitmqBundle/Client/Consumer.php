<?php
namespace IvixLabs\RabbitmqBundle\Client;


use IvixLabs\RabbitmqBundle\Annotation;
use IvixLabs\RabbitmqBundle\Connection\ConnectionFactory;
use IvixLabs\RabbitmqBundle\Message\MessageInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use PhpAmqpLib\Message\AMQPMessage;

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
                    $this->consumerSettings->attach($annotation, $method);
                    $id = $annotation->exchange['name'] . '_' . $annotation->queue['name'];
                    $this->taskClasses[$id] = [$taskClass, $annotation];
                }
            }
        }
    }


    public function execute()
    {

        $connection = $this->connectionFactory->getConnection($this->connectionName);
        $channel = $connection->channel();

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

            list($queueName, ,) = $channel->queue_declare(
                $consumer->queue['name'],
                false,
                $queueDurable,
                $queueExclusive,
                $queueAutoDelete
            );

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

                $channel->exchange_declare(
                    $consumer->exchange['name'],
                    $consumer->exchange['type'],
                    false, $exchangeDurable, $exchangeAutoDelete);

                $channel->queue_bind($queueName, $consumer->exchange['name']);
            }

            $channel->basic_qos(null, 1, null);

            $closure = $method->getClosure($this->consumer);
            $callback = function (AMQPMessage $msg) use ($closure) {
                $id = $msg->get('exchange') . '_' . $msg->get('routing_key');
                /** @var Consumer $annotation */
                list($taskClass, $annotation) = $this->taskClasses[$id];

                if ($taskClass !== false) {
                    /** @var MessageInterface $task */
                    $task = new $taskClass();
                    $task->fromString($msg->body);
                    $result = $closure($task);
                } else {
                    $result = $closure();
                }

                if ($annotation->ack && $result) {
                    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                }
            };
            $channel->basic_consume($queueName, '', false, !$consumer->ack, false, false, $callback);
        }

        while (count($channel->callbacks)) {
            $channel->wait();
        }
        $channel->close();
        $connection->close();
    }

}