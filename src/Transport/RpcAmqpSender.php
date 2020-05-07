<?php

namespace SCode\AmqpExtraBundle\Transport;

use SCode\AmqpExtraBundle\Serialization\RpcSerializer;
use SCode\AmqpExtraBundle\Stamp\ReplyReceiverStamp;
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

    /**
     * @var RpcSerializer
     */
    private $rpcSerializer;

    public function __construct(
        RpcConnection $connection,
        RpcSerializer $rpcSerializer,
        SenderInterface $original
    ) {
        $this->connection = $connection;
        $this->rpcSerializer = $rpcSerializer;
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
            ->with(new ReplyReceiverStamp($replyReceiver));
    }

    protected function buildReplyReceiver(): callable
    {
        return function () {
            $envelop = $this->connection->get();
            $body = $envelop->getBody();

            return $this->rpcSerializer->decode([
                'body' => false === $body ? '' : $body,
                'headers' => $envelop->getHeaders(),
            ]);
        };
    }
}
