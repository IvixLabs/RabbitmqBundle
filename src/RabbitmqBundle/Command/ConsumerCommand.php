<?php
namespace IvixLabs\RabbitmqBundle\Command;

use IvixLabs\RabbitmqBundle\Client\Consumer;
use IvixLabs\RabbitmqBundle\Client\ConsumerWorkerManager;
use IvixLabs\RabbitmqBundle\Connection\ConnectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumerCommand extends Command
{

    /**
     * @var ConsumerWorkerManager
     */
    private $consumerWorkerManager;

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    protected function configure()
    {
        $this->setName('ivixlabs:rabbitmq:consumer_worker')
            ->setDescription('Launch consumer worker')
            ->addArgument('name', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $consumerWorker = $this->consumerWorkerManager->getConsumerWorker($name);

        $launcher = new Consumer($consumerWorker, $this->connectionFactory);
        $launcher->execute();
    }

    public function setConsumerWorkerManager(ConsumerWorkerManager $consumerWorkerManager)
    {
        $this->consumerWorkerManager = $consumerWorkerManager;
    }

    public function setConnectionFactory(ConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }
}