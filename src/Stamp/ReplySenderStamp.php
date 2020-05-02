<?php

namespace SCode\AmqpRpcTransportBundle\Stamp;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class ReplySenderStamp implements NonSendableStampInterface
{
    /**
     * @var callable
     */
    private $sender;

    public function __construct(callable $sender)
    {
        $this->sender = $sender;
    }

    public function getSender(): callable
    {
        return $this->sender;
    }
}
