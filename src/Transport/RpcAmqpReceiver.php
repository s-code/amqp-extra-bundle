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
            /** @var AmqpReceivedStamp $receivedStamp */
            $receivedStamp = $envelope->last(AmqpReceivedStamp::class);
            $replyTo = $receivedStamp->getAmqpEnvelope()->getReplyTo();

            $replySender = function (string $response) use ($replyTo) {
                $this->connection->reply($response, $replyTo);
            };

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
}
