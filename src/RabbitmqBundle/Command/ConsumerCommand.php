<?php
namespace IvixLabs\RabbitmqBundle\Command;

use IvixLabs\RabbitmqBundle\Client\Consumer;
use IvixLabs\RabbitmqBundle\Client\ConsumerManager;
use IvixLabs\RabbitmqBundle\Connection\ConnectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumerCommand extends Command
{

    /**
     * @var ConsumerManager
     */
    private $consumerManager;

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    protected function configure()
    {
        $this->setName('ivixlabs:rabbitmq:consumer')
            ->setDescription('Launch consumer')
            ->addArgument('name', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $consumer = $this->consumerManager->getConsumer($name);

        $launcher = new Consumer($consumer, $this->connectionFactory);
        $launcher->execute();
    }

    public function setConsumerManager(ConsumerManager $consumerManager)
    {
        $this->consumerManager = $consumerManager;
    }

    public function setConnectionFactory(ConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }
}