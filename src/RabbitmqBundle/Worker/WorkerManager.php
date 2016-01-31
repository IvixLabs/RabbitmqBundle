<?php
namespace IvixLabs\RabbitmqBundle\Worker;

class WorkerManager
{

    private $workers = [];

    public function addWorkerService($service, $id)
    {
        $this->workers[$id] = $service;
    }


    public function getWorker($id)
    {
        return $this->workers[$id];
    }

}