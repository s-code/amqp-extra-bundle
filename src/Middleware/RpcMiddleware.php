<?php

namespace SCode\AmqpRpcTransportBundle\Middleware;

use SCode\AmqpRpcTransportBundle\Stamp\ReplySenderStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class RpcMiddleware  implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $finalEnvelop = $stack->next()->handle($envelope, $stack);

        /** @var ReplySenderStamp|null $replyStamp */
        $replyStamp = $finalEnvelop->last(ReplySenderStamp::class);
        /** @var HandledStamp|null $handledStamp */
        $handledStamp = $finalEnvelop->last(HandledStamp::class);

        if (null === $replyStamp || null === $handledStamp) {
            return $finalEnvelop;
        }

        $replyStamp->getSender()($finalEnvelop);

        return $finalEnvelop->withoutAll(ReplySenderStamp::class);
    }
}