<?php

namespace SCode\AmqpRpcTransportBundle\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpReceivedStamp;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;

class RpcAmqpReceiver implements ReceiverInterface, MessageCountAwareInterface
{
    /**
     * @var RpcConnection
     */
    private $connection;

    /**
     * @var ReceiverInterface&MessageCountAwareInterface
     */
    private $original;

    /**
     * @param RpcConnection $connection
     * @param ReceiverInterface&MessageCountAwareInterface $original
     */
    public function __construct(RpcConnection $connection, $original)
    {
        $this->connection = $connection;
        $this->original = $original;
    }

    public function get(): iterable
    {
        foreach ($this->original->get() as $envelope) {
            /** @var \AMQPEnvelope $amqpEnvelop */
            $amqpEnvelop = $envelope->last(AmqpReceivedStamp::class)->getAmqpEnvelope();

            $replySender = $this->buildReplySender($amqpEnvelop);

            yield $envelope->with(new AmqpReplySenderStamp($replySender));
        }
    }

    public function ack(Envelope $envelope): void
    {
        $this->original->ack($envelope);
    }

    public function reject(Envelope $envelope): void
    {
        $this->original->reject($envelope);
    }

    public function getMessageCount(): int
    {
        return $this->original->getMessageCount();
    }

    protected function buildReplySender(\AMQPEnvelope $amqpEnvelop): callable
    {
        return function ($result) use ($amqpEnvelop) {
            $data = $this->rpcSerializer->serialize($amqpEnvelop, $result);

            $this->connection->reply($data['body'], $replyTo, $data['attributes']);
        };
    }
}
