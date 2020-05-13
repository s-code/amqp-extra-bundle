<?php

namespace SCode\AmqpExtraBundle\Transport;

class ExtraAmqpQueue extends \AMQPQueue
{
    private const DEFAULT_EXCHANGE = '';

    public function setArguments(array $arguments)
    {
        if (isset($arguments['x-dead-letter-exchange'])) {
            $arguments['x-dead-letter-exchange'] = self::DEFAULT_EXCHANGE;
        }

        parent::setArguments($arguments);
    }

    public function bind($exchange_name, $routing_key = null, $arguments = null)
    {
        if ($exchange_name === self::DEFAULT_EXCHANGE) {
            return true;
        }

        return parent::bind($exchange_name, $routing_key, $arguments);
    }
}