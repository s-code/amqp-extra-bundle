<?php

namespace SCode\AmqpExtraBundle\Serialization;

interface HeadersConverterInterface
{
    public const STAMP_HEADER_PREFIX = 'X-Message-Stamp-';

    public function toSharedFormat(array $encodedEnvelope): array;

    public function fromSharedFormat(array $encodedEnvelope): array;
}