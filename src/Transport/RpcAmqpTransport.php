<?php

namespace SCode\AmqpRpcTransportBundle\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpReceiver;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpSender;
use Symfony\Component\Messenger\Transport\AmqpExt\Connection;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\SetupableTransportInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class RpcAmqpTransport implements TransportInterface, SetupableTransportInterface, MessageCountAwareInterface
{
    /**
     * @var SerializerInterface 
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

    public function __construct(Connection $connection, SerializerInterface $serializer = null)
    {
        $this->connection = new RpcConnection($connection);
        $this->serializer = $serializer ?? new PhpSerializer();
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
            new AmqpReceiver($this->connection->getWrapped(), $this->serializer)
        );

        return $this->receiver;
    }

    private function getSender(): RpcAmqpSender
    {
        $this->sender = new RpcAmqpSender(
            $this->connection,
            new AmqpSender($this->connection->getWrapped(), $this->serializer)
        );

        return $this->sender;
    }
}
