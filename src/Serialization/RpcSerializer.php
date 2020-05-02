<?php

namespace SCode\AmqpRpcTransportBundle\Serialization;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\SerializerStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class RpcSerializer implements SerializerInterface
{
    /**
     * @var SerializerInterface
     */
    private $original;

    /**
     * @var string[]
     */
    private $stampsToClone;

    /**
     * @param SerializerInterface $original
     * @param string[] $stampsToClone
     */
    public function __construct(SerializerInterface $original, array $stampsToClone = [])
    {
        $this->original = $original;
        $this->stampsToClone = array_merge($stampsToClone, [SerializerStamp::class]);
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        return $this->original->decode($encodedEnvelope);
    }

    public function encode(Envelope $envelope): array
    {
        /** @var HandledStamp|null $handledStamp */
        $handledStamp = $envelope->last(HandledStamp::class);

        $replyMessage = $this->buildReplyMessage($handledStamp);
        $replyStamps = $this->buildReplyStamps($envelope->all());

        return $this->original->encode(new Envelope($replyMessage, $replyStamps));
    }

    public function getWrapped(): SerializerInterface
    {
        return $this->original;
    }

    protected function buildReplyMessage(?HandledStamp $handledStamp)
    {
        $result = $handledStamp ? $handledStamp->getResult() : null;

        if (is_object($result)) {
            return $result;
        }

        return new \ArrayObject(['result' => $result]);
    }

    private function buildReplyStamps(array $originalStamps): array
    {
        $stamps = [];

        foreach ($this->stampsToClone as $stampsName) {
            if (isset($originalStamps[$stampsName])) {
                $stamps[$stampsName] = $originalStamps[$stampsName];
            }
        }

        return $stamps;
    }
}