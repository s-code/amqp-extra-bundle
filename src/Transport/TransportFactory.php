<?php

namespace SCode\AmqpExtraBundle\Transport;

use Symfony\Component\Messenger\Transport\AmqpExt\Connection;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class TransportFactory implements TransportFactoryInterface
{
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        unset($options['transport_name']);

        if (!isset($options['delay']['exchange_name'])) {
            $options['delay']['exchange_name'] = '';
        }

        $connection = Connection::fromDsn($dsn, $options, new ExtraAmqpFactory($options['delay']));

        if (empty($options['rpc'])) {
            return new ExtraAmqpTransport($connection, $serializer);
        }

        return new RpcAmqpTransport($connection, $serializer);
    }

    public function supports(string $dsn, array $options): bool
    {
        return 0 === strpos($dsn, 'amqp://');
    }
}
