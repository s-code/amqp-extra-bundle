<?php

namespace SCode\AmqpRpcTransportBundle\Transport;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class AmqpReplyReceiverStamp implements NonSendableStampInterface
{
    /**
     * @var callable
     */
    private $receiver;

    public function __construct(callable $reciever)
    {
        $this->receiver = $reciever;
    }

    public function getReceiver(): callable
    {
        return $this->receiver;
    }
}
