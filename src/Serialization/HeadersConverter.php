<?php

namespace SCode\AmqpExtraBundle\Serialization;

use SCode\AmqpExtraBundle\Middleware\RoutingStrategyInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpStamp;

class HeadersConverter implements HeadersConverterInterface
{
    private const DEFAULT_SHARED_TYPE = 'array';

    /**
     * @var RoutingStrategyInterface
     */
    private $routingStrategy;

    /**
     * @var array
     */
    private $routingContext;

    /**
     * @var array
     */
    private $headersMap;

    public function __construct(
        RoutingStrategyInterface $routingStrategy,
        array $routingContext,
        array $headersMap
    ) {
        $this->routingStrategy = $routingStrategy;
        $this->routingContext = $routingContext;
        $this->headersMap = ['to' => $headersMap, 'from' => array_flip($headersMap)];
    }

    public function toSharedFormat(array $encodedEnvelope): array
    {
        $newHeaders = [];

        foreach ($encodedEnvelope['headers'] ?? [] as $name => $header) {
            $newName = $this->headersMap['to'][$name] ?? null;

            if ($newName) {
                $newHeaders[$newName] = $header;
            }
        }

        $newHeaders['type'] = $this->normalizeType($encodedEnvelope);

        return $newHeaders;
    }

    public function fromSharedFormat(array $encodedEnvelope): array
    {
        $newHeaders = [];

        foreach ($encodedEnvelope['headers'] ?? [] as $name => $header) {
            $newName = $this->headersMap['from'][$name] ?? null;

            if ($newName) {
                $newHeaders[$newName] = $header;
            }
        }

        $newHeaders['type'] = $this->denormalizeType($encodedEnvelope);

        return $newHeaders;
    }

    protected function denormalizeType(array $envelope): ?string
    {
        $type = $envelope['headers']['type'] ?? null;

        if ($type === null) {
            return null;
        }

        if ($type === self::DEFAULT_SHARED_TYPE) {
            return \ArrayObject::class;
        }

        return $this->routingStrategy->getClass($type, $this->routingContext + ['encoded_envelop' => $envelope]);
    }

    protected function normalizeType(array $envelope): ?string
    {
        $type = $envelope['headers']['type'] ?? null;

        if (null === $type) {
            return null;
        }

        if ($type === \ArrayObject::class) {
            return self::DEFAULT_SHARED_TYPE;
        }

        return $this->routingStrategy->getRoutingKey($type, $this->routingContext + ['encoded_envelop' => $envelope]);
    }
}