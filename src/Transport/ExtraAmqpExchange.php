<?php

namespace SCode\AmqpExtraBundle\Transport;

class ExtraAmqpExchange extends \AMQPExchange
{
    public function declareExchange()
    {
        if (!$this->getName()) {
            return true;
        }

        return parent::declareExchange();
    }
}