<?php

namespace SCode\AmqpRpcTransportBundle\Transport;

use SCode\AmqpRpcTransportBundle\Serialization\RpcSerializerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpStamp;
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

    public function __construct(
        RpcConnection $connection,
        RpcSerializerInterface $rpcSerializer,
        SenderInterface $original
    ) {
        $this->connection = $connection;
        $this->original = $original;
    }

    public function send(Envelope $envelope): Envelope
    {
        $replyTo = $this->connection->initReplyQueue();
        $prevAmqpStamp = $envelope->last(AmqpStamp::class);
        $newAmqpStamp = AmqpStamp::createWithAttributes(['reply_to' => $replyTo], $prevAmqpStamp);

        $replyReceiver = $this->buildReplyReceiver();

        return $this->original
            ->send($envelope->with($newAmqpStamp))
            ->with(new AmqpReplyReceiverStamp($replyReceiver));
    }

    protected function buildReplyReceiver(): callable
    {
        return static function () {
            $envelop = $this->connection->get();

            return $this->rpcSerializer->deserialize($envelop);
        };
    }
}
