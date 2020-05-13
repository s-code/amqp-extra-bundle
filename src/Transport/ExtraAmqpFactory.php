<?php

namespace SCode\AmqpExtraBundle\Transport;

use Symfony\Component\Messenger\Transport\AmqpExt\AmqpFactory;

class ExtraAmqpFactory extends AmqpFactory
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
            return new ExtraAmqpQueue($channel);
        }

        return parent::createQueue($channel);
    }

    public function createExchange(\AMQPChannel $channel): \AMQPExchange
    {
        if ($this->usedDefaultDelayExchange()) {
            return new ExtraAmqpExchange($channel);
        }

        return parent::createExchange($channel);
    }

    private function usedDefaultDelayExchange(): bool
    {
        return $this->delayConfig['exchange_name'] === '';
    }
}
