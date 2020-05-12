<?php

namespace SCode\AmqpExtraBundle\Transport;

use SCode\AmqpExtraBundle\Serialization\SharedTransportSerializerInterface;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpTransport;
use Symfony\Component\Messenger\Transport\AmqpExt\Connection;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class TransportFactory implements TransportFactoryInterface
{
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        unset($options['transport_name']);

        if ($serializer instanceof SharedTransportSerializerInterface) {
            $options['delay']['exchange_name'] = '';
        }

        $connection = Connection::fromDsn($dsn, $options, new AmqpFactory());

        if (empty($options['rpc'])) {
            return new AmqpTransport($connection, $serializer);
        }

        return new RpcAmqpTransport($connection, $serializer);
    }

    public function supports(string $dsn, array $options): bool
    {
        return 0 === strpos($dsn, 'amqp://');
    }
}
