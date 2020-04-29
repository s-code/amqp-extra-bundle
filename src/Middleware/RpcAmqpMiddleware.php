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
        /** @var AmqpReplySenderStamp $replyStamp */
        $replyStamp = $envelope->last(AmqpReplySenderStamp::class);

        /** @var HandledStamp $handledStamp */
        $handledStamp = $envelope->last(HandledStamp::class);

        if (null !== $replyStamp && null !== $handledStamp) {
            $replyStamp->getSender()($handledStamp->getResult() ?? '');
            $envelope = $envelope->withoutAll(AmqpReplySenderStamp::class);
        }

        return $stack->next()->handle($envelope, $stack);
    }
}