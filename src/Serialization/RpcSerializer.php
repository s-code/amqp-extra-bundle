<?php

namespace SCode\AmqpRpcTransportBundle\Serialization;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class RpcSerializer implements RpcSerializerInterface
{
    public function deserialize(array $message)
    {

    }

    public function serialize(Envelope $mrequestEnvelop, $result): array
    {

    }
}