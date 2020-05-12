<?php

namespace SCode\AmqpExtraBundle\Transport;

class AMQPExchange extends \AMQPExchange
{
    public function declareExchange()
    {
        if ($this->getName() === '') {
            return true;
        }

        return parent::declareExchange();
    }
}