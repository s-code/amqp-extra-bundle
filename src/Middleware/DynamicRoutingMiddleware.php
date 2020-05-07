<?php

namespace SCode\AmqpExtraBundle\Middleware;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpStamp;

class DynamicRoutingMiddleware implements MiddlewareInterface
{
    /**
     * @var RoutingMap
     */
    private $routingMap;

    public function __construct(RoutingMap $routingMap)
    {
        $this->routingMap = $routingMap;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (null === $envelope->last(AmqpStamp::class)) {
            $class = get_class($envelope->getMessage());
            $routingKey = $this->routingMap->getRoutingKey($class);

            if ($routingKey === null) {
                throw new \RuntimeException(sprintf('Unable to determine routing key for class "%s"', $class));
            }

            $envelope = $envelope->with(new AmqpStamp($routingKey));
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
