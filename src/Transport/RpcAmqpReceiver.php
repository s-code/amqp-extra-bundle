<?php

namespace SCode\AmqpRpcTransportBundle\Transport;

use SCode\AmqpRpcTransportBundle\Serialization\RpcSerializer;
use SCode\AmqpRpcTransportBundle\Stamp\ReplySenderStamp;
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
     * @var RpcSerializer
     */
    private $rpcSerializer;

    /**
     * @param RpcConnection $connection
     * @param RpcSerializer $rpcSerializer
     * @param ReceiverInterface&MessageCountAwareInterface $original
     */
    public function __construct(RpcConnection $connection, RpcSerializer $rpcSerializer, $original)
    {
        $this->connection = $connection;
        $this->rpcSerializer = $rpcSerializer;
        $this->original = $original;
    }

    public function get(): iterable
    {
        foreach ($this->original->get() as $envelope) {
            /** @var AmqpReceivedStamp|null $receivedStamp */
            $receivedStamp = $envelope->last(AmqpReceivedStamp::class);
            $replyTo = $receivedStamp ? $receivedStamp->getAmqpEnvelope()->getReplyTo() : null;

            if ($replyTo === null) {
                throw new \RuntimeException('Unable to determine reply queue name from received message');
            }

            $replySender = $this->buildReplySender($replyTo);

            yield $envelope->with(new ReplySenderStamp($replySender));
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

    protected function buildReplySender(string $replyTo): callable
    {
        return function (Envelope $envelope) use ($replyTo) {
            $data = $this->rpcSerializer->encode($envelope);

            $this->connection->reply($replyTo, $data['body'], $data['headers']);
        };
    }
}
