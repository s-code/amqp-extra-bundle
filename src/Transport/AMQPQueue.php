<?php

namespace SCode\AmqpExtraBundle\Transport;

class AMQPQueue extends \AMQPQueue
{
    public function bind($exchange_name, $routing_key = null, array $arguments = array())
    {
        if ($exchange_name === '') {
            return true;
        }

        return parent::bind($exchange_name, $routing_key, $arguments);
    }
}