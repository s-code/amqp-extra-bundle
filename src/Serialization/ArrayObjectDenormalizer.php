<?php

namespace SCode\AmqpRpcTransportBundle\Serialization;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ArrayObjectDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        return new \ArrayObject((array) $data);
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type === \ArrayObject::class;
    }
}