<?php

namespace SCode\AmqpExtraBundle\Routing;

interface RoutingStrategyInterface
{
    public function getClass(string $routingKey, array $context): ?string;

    public function getRoutingKey(string $class, array $context): ?string;
}