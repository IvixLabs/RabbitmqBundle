<?php
namespace IvixLabs\RabbitmqBundle\Worker;


use IvixLabs\RabbitmqBundle\Annotation\Consumer;
use IvixLabs\RabbitmqBundle\Annotation\ConsumerConnection;
use IvixLabs\RabbitmqBundle\Connection\ConnectionFactory;
use IvixLabs\RabbitmqBundle\Message\MessageInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use AMQPEnvelope;

class WorkerLauncher
{

    private $worker;

    private $connectionName;

    private $iterations;

    /**
     * @var \SplObjectStorage
     */
    private $consumers;

    private $taskClasses = [];

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    public function __construct($worker, ConnectionFactory $connectionFactory)
    {
        $this->consumers = new \SplObjectStorage();

        $this->connectionFactory = $connectionFactory;
        $this->worker = $worker;

        $className = get_class($worker);
        $reflectionClass = new \ReflectionClass($className);
        $reader = new AnnotationReader();

        $classAnnotations = $reader->getClassAnnotations($reflectionClass);

        foreach ($classAnnotations as $annotation) {
            if ($annotation instanceof ConsumerConnection) {
                $this->connectionName = $annotation->name;
                $this->iterations = $annotation->iterations;
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
                if ($annotation instanceof Consumer) {
                    $this->consumers->attach($annotation, $method);
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
        foreach ($this->consumers as $consumer) {
            $method = $this->consumers[$consumer];


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

            $closure = $method->getClosure($this->worker);
            $callback = function (AMQPEnvelope $msg) use ($closure) {
                $id = $msg->getExchangeName() . '_' . $msg->getRoutingKey();
                /** @var Consumer $annotation */
                list($taskClass, $annotation) = $this->taskClasses[$id];

                if($taskClass !== false) {
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

        $currentIteration = 0;
        while (count($channel->callbacks) && $currentIteration < $this->iterations) {
            $channel->wait();
            $currentIteration++;
        }
        $channel->close();
        $connection->close();
    }

}