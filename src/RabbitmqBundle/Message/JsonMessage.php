<?php
namespace IvixLabs\RabbitmqBundle\Message;


class JsonMessage extends AbstractMessage
{
    /**
     * @param $string
     */
    public function fromString($string)
    {
        $this->data = (json_decode($string));
    }

    /**
     * @return string
     */
    public function toString()
    {
        return json_encode((string)$this->data);
    }


}