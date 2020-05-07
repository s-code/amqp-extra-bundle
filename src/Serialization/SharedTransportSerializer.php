<?php

namespace SCode\AmqpExtraBundle\Serialization;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpStamp;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class SharedTransportSerializer implements SerializerInterface
{
    private const STAMP_HEADER_PREFIX = 'X-Message-Stamp-';
    private const DEFAULT_SHARED_TYPE = 'array';

    /**
     * @var Serializer
     */
    private $originalSerializer;

    /**
     * @var string
     */
    private $busName;

    /**
     * @var string
     */
    private $busNameHeader;

    /**
     * @var RoutingMap
     */
    private $routingMap;

    public function __construct(string $busName, Serializer $originalSerializer, RoutingMap $routingMap)
    {
        $this->busNameHeader = self::STAMP_HEADER_PREFIX . BusNameStamp::class;
        $this->originalSerializer = $originalSerializer;
        $this->busName = $busName;
        $this->routingMap = $routingMap;
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        $busNameStamp = $encodedEnvelope['headers'][$this->busNameHeader] ?? null;

        if ($busNameStamp !== null) {
            return $this->originalSerializer->decode($encodedEnvelope);
        }

        $encodedEnvelope['headers']['type'] = $this->decodeType($encodedEnvelope['headers']);

        return $this->originalSerializer
            ->decode($encodedEnvelope)
            ->with(new BusNameStamp($this->busName));
    }

    public function encode(Envelope $envelope): array
    {
        $result = $this->originalSerializer->encode($envelope);

        if ($envelope->last(ReceivedStamp::class)) {
            return $result;
        }

        $headers = [];
        foreach ($result['headers'] as $key => $header) {
            if (strpos($key, self::STAMP_HEADER_PREFIX) === false) {
                $headers[$key] = $header;
            }
        }

        $headers['type'] = $this->encodeType($envelope);

        return [
            'body' => $result['body'],
            'headers' => $headers
        ];
    }

    private function decodeType(array $headers): ?string
    {
        $type = $headers['type'] ?? null;

        if ($type === self::DEFAULT_SHARED_TYPE) {
            return \ArrayObject::class;
        }

        return $type === null ? null : $this->routingMap->getClass($type);
    }

    private function encodeType(Envelope $envelope): string
    {
        $amqpStamp = $envelope->last(AmqpStamp::class);

        if ($amqpStamp instanceof AmqpStamp) {
            return $amqpStamp->getRoutingKey();
        }

        return self::DEFAULT_SHARED_TYPE;
    }
}