<?php

namespace SCode\AmqpRpcTransportBundle\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;

class RpcAmqpSender implements SenderInterface
{
    /**
     * @var RpcConnection
     */
    private $connection;

    /**
     * @var SenderInterface
     */
    private $original;

    public function __construct(RpcConnection $connection, SenderInterface $original)
    {
        $this->connection = $connection;
        $this->original = $original;
    }

    public function send(Envelope $envelope): Envelope
    {
        $replyReceiver = function () {
            $envelop = $this->connection->get();

            return $envelop ? $envelop->getBody() : null;
        };
        
        return $this->original
            ->send($envelope)
            ->with(new AmqpReplyReceiverStamp($replyReceiver));
    }
}
