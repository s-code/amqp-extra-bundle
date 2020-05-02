<?php

namespace SCode\AmqpRpcTransportBundle\Transport;

use SCode\AmqpRpcTransportBundle\Serialization\RpcSerializer;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpReceiver;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpSender;
use Symfony\Component\Messenger\Transport\AmqpExt\Connection;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\SetupableTransportInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class RpcAmqpTransport implements TransportInterface, SetupableTransportInterface, MessageCountAwareInterface
{
    /**
     * @var RpcSerializer
     */
    private $serializer;

    /**
     * @var RpcConnection
     */
    private $connection;

    /**
     * @var RpcAmqpReceiver
     */
    private $receiver;

    /**
     * @var RpcAmqpSender
     */
    private $sender;

    public function __construct(Connection $connection, SerializerInterface $serializer)
    {
        $this->connection = new RpcConnection($connection);
        $this->serializer = new RpcSerializer($serializer);
    }

    public function get(): iterable
    {
        return ($this->receiver ?? $this->getReceiver())->get();
    }

    public function ack(Envelope $envelope): void
    {
        ($this->receiver ?? $this->getReceiver())->ack($envelope);
    }

    public function reject(Envelope $envelope): void
    {
        ($this->receiver ?? $this->getReceiver())->reject($envelope);
    }

    public function send(Envelope $envelope): Envelope
    {
        return ($this->sender ?? $this->getSender())->send($envelope);
    }

    public function setup(): void
    {
        $this->connection->getWrapped()->setup();
    }

    public function getMessageCount(): int
    {
        return ($this->receiver ?? $this->getReceiver())->getMessageCount();
    }

    private function getReceiver(): RpcAmqpReceiver
    {
        $this->receiver = new RpcAmqpReceiver(
            $this->connection,
            $this->serializer,
            new AmqpReceiver($this->connection->getWrapped(), $this->serializer->getWrapped())
        );

        return $this->receiver;
    }

    private function getSender(): RpcAmqpSender
    {
        $this->sender = new RpcAmqpSender(
            $this->connection,
            $this->serializer,
            new AmqpSender($this->connection->getWrapped(), $this->serializer->getWrapped())
        );

        return $this->sender;
    }
}
