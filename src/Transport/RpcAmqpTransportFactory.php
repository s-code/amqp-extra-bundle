<?php

namespace SCode\AmqpRpcTransportBundle\Transport;

use Symfony\Component\Messenger\Transport\AmqpExt\AmqpTransportFactory;
use Symfony\Component\Messenger\Transport\AmqpExt\Connection;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class RpcAmqpTransportFactory implements TransportFactoryInterface
{
    /***
     * @var AmqpTransportFactory
     */
    private $original;

    public function __construct(AmqpTransportFactory $original)
    {
        $this->original = $original;
    }

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        if (!$this->hasRpcProtocol($dsn)) {
            return $this->original->createTransport($dsn, $options, $serializer);
        }

        unset($options['transport_name']);

        return new RpcAmqpTransport(Connection::fromDsn($dsn, $options), $serializer);
    }

    public function supports(string $dsn, array $options): bool
    {
        return $this->original->supports($dsn, $options) || $this->hasRpcProtocol($dsn);
    }
    
    private function hasRpcProtocol(string $dsn): bool
    {
        return 0 === strpos($dsn, 'amqp-rpc://');
    }
}
