<?php

namespace SCode\AmqpExtraBundle\Transport;

use Symfony\Component\Messenger\Transport\AmqpExt\AmqpFactory as SymfonyAmqpFactory;

class AmqpFactory extends SymfonyAmqpFactory
{
    /**
     * @var array
     */
    private $delayConfig;

    public function __construct(array $delayConfig)
    {
        $this->delayConfig = $delayConfig;
    }

    public function createQueue(\AMQPChannel $channel): \AMQPQueue
    {
        if ($this->usedDefaultDelayExchange()) {
            return new ExtraAMQPQueue($channel);
        }

        return parent::createQueue($channel);
    }

    public function createExchange(\AMQPChannel $channel): \AMQPExchange
    {
        if ($this->usedDefaultDelayExchange()) {
            return new ExtraAMQPExchange($channel);
        }

        return parent::createExchange($channel);
    }

    private function usedDefaultDelayExchange(): bool
    {
        return $this->delayConfig['exchange_name'] === '';
    }
}
