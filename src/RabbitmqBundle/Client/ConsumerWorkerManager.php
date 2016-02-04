<?php
namespace IvixLabs\RabbitmqBundle\Client;

class ConsumerWorkerManager
{
    private $consumerWorkers = [];

    public function addConsumerWorker($service, $id)
    {
        $this->consumerWorkers[$id] = $service;
    }

    public function getConsumerWorker($id)
    {
        if (!isset($this->consumerWorkers[$id])) {
            throw new \LogicException('No found consumer worker = ' . $id);
        }
        return $this->consumerWorkers[$id];
    }
}