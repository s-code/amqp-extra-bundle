<?php

namespace SCode\AmqpRpcTransportBundle\Middleware;

use SCode\AmqpRpcTransportBundle\Transport\AmqpReplySenderStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class RpcAmqpMiddleware  implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $finalEnvelop = $stack->next()->handle($envelope, $stack);

        /** @var AmqpReplySenderStamp|null $replyStamp */
        $replyStamp = $finalEnvelop->last(AmqpReplySenderStamp::class);
        /** @var HandledStamp $handledStamp */
        $handledStamp = $finalEnvelop->last(HandledStamp::class);

        if (null === $replyStamp || null === $handledStamp) {
            return $finalEnvelop;
        }

        $replyStamp->getSender()($handledStamp->getResult());

        return $finalEnvelop->withoutAll(AmqpReplySenderStamp::class);
    }
}