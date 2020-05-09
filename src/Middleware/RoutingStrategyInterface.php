<?php

namespace SCode\AmqpExtraBundle\Middleware;

interface RoutingStrategyInterface
{
    public function getClass(string $routingKey, array $context): ?string;

    public function getRoutingKey(string $class, array $context): ?string;
}