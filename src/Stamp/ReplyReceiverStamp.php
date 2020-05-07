<?php

namespace SCode\AmqpExtraBundle\Stamp;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class ReplyReceiverStamp implements NonSendableStampInterface
{
    /**
     * @var callable
     */
    private $receiver;

    public function __construct(callable $receiver)
    {
        $this->receiver = $receiver;
    }

    public function getReceiver(): callable
    {
        return $this->receiver;
    }
}
