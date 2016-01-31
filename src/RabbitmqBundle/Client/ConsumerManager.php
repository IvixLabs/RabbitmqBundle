<?php
namespace IvixLabs\RabbitmqBundle\Client;

class ConsumerManager
{

    private $consumers = [];

    public function addConsumerService($service, $id)
    {
        $this->consumers[$id] = $service;
    }


    public function getConsumer($id)
    {
        return $this->consumers[$id];
    }

}