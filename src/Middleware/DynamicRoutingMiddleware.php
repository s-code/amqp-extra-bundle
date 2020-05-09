<?php

namespace SCode\AmqpExtraBundle\Middleware;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpReceivedStamp;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpStamp;

class DynamicRoutingMiddleware implements MiddlewareInterface
{
    /**
     * @var RoutingStrategyInterface
     */
    private $strategy;

    /**
     * @var array
     */
    private $routingContext;

    public function __construct(RoutingStrategyInterface $strategy, array $routingContext)
    {
        $this->strategy = $strategy;
        $this->routingContext = $routingContext;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (null === $envelope->last(AmqpStamp::class) && null === $envelope->last(AmqpReceivedStamp::class)) {
            $class = get_class($envelope->getMessage());
            $routingKey = $this->strategy->getRoutingKey(
                $class,
                $this->routingContext + ['envelop' => $envelope]
            );

            if ($routingKey === null) {
                throw new \RuntimeException(sprintf('Unable to determine routing key for class "%s"', $class));
            }

            $envelope = $envelope->with(new AmqpStamp($routingKey));
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
