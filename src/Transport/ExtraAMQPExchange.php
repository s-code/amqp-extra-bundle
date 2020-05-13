<?php

namespace SCode\AmqpExtraBundle\Transport;

class ExtraAMQPExchange extends \AMQPExchange
{
    public function declareExchange()
    {
        if (!$this->getName()) {
            return true;
        }

        return parent::declareExchange();
    }
}