<?php

namespace SCode\AmqpExtraBundle\Transport;

use Symfony\Component\Messenger\Transport\AmqpExt\AmqpFactory as SymfonyAmqpFactory;

class AmqpFactory extends SymfonyAmqpFactory
{
    public function createQueue(\AMQPChannel $channel): \AMQPQueue
    {
        return new AMQPQueue($channel);
    }

    public function createExchange(\AMQPChannel $channel): \AMQPExchange
    {
        return new AMQPExchange($channel);
    }
}
