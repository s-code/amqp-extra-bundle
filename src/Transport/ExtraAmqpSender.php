<?php

namespace SCode\AmqpExtraBundle\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpReceivedStamp;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpSender as SymfonyAmqpSender;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpStamp;

class ExtraAmqpSender extends SymfonyAmqpSender
{
    public function send(Envelope $envelope): Envelope
    {
        return parent::send($this->addOriginHeadersForRedelivery($envelope));
    }

    protected function addOriginHeadersForRedelivery(Envelope $envelope): Envelope
    {
        $redeliveryStamp = $envelope->last(RedeliveryStamp::class);

        if (!$redeliveryStamp instanceof RedeliveryStamp) {
            return $envelope;
        }

        $receivedStamp = $envelope->last(AmqpReceivedStamp::class);

        if (!$receivedStamp instanceof AmqpReceivedStamp) {
            return $envelope;
        }

        /** @var AmqpStamp|null $oldAmqpStamp */
        $oldAmqpStamp = $envelope->last(AmqpStamp::class);
        $newAmqpStamp = new AmqpStamp(
            $receivedStamp->getQueueName(),
            AMQP_NOPARAM,
            $oldAmqpStamp ? $oldAmqpStamp->getAttributes() : []
        );

        return $envelope->with($newAmqpStamp);
    }
}