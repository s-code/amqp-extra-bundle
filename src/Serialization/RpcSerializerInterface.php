<?php

namespace SCode\AmqpRpcTransportBundle\Serialization;

use Symfony\Component\Messenger\Envelope;

interface RpcSerializerInterface
{
    public function deserialize(\AMQPEnvelope $envelope);

    public function serialize(\AMQPEnvelope $requestEnvelope, $result): array;
}