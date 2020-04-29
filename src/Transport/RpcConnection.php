<?php

namespace SCode\AmqpRpcTransportBundle\Transport;

use Symfony\Component\Messenger\Transport\AmqpExt\AmqpStamp;
use Symfony\Component\Messenger\Transport\AmqpExt\Connection;

class RpcConnection
{
    private const DIRECT_REPLY_QUEUE = 'amq.rabbitmq.reply-to';

    /**
     * @var Connection
     */
    private $original;

    /**
     * @var \AMQPExchange|null
     */
    private $replyExchange;
    
    /**
     * @var \AMQPQueue|null
     */
    private $replyQueue;

    public function __construct(Connection $original)
    {
        $this->original = $original;
    }

    public function reply(string $response, string $replyTo): void
    {
        $this->clearWhenDisconnected();
        
        if (null === $this->replyExchange) {
            $this->replyExchange = new \AMQPExchange($this->original->channel());
        }

        $this->replyExchange->publish($response, $replyTo, AMQP_NOPARAM);
    }
    
    public function get(): ?\AMQPEnvelope
    {
        $response = null;

        $this->initReplyQueue()->consume(function (\AMQPEnvelope $envelope) use (&$response) {
            $response = $envelope;

            return false;
        }, AMQP_JUST_CONSUME);

        return $response;
    }

    public function publish(string $body, array $headers = [], int $delayInMs = 0, AmqpStamp $amqpStamp = null): void
    {
        $this->initReplyQueue();
        
        $this->original->publish(
            $body,
            $headers,
            $delayInMs,
            AmqpStamp::createWithAttributes(['reply_to' => self::DIRECT_REPLY_QUEUE], $amqpStamp)
        );
    }
    
    public function getWrapped(): Connection
    {
        return $this->original;
    }

    private function initReplyQueue(): \AMQPQueue
    {
        $this->clearWhenDisconnected();

        if (null === $this->replyQueue) {
            $this->replyQueue = new \AMQPQueue($this->original->channel());
            $this->replyQueue->setName(self::DIRECT_REPLY_QUEUE);
            $this->replyQueue->consume(null, AMQP_AUTOACK);
        }

        return $this->replyQueue;
    }

    private function clearWhenDisconnected(): void
    {
        if (!$this->original->channel()->isConnected()) {
            $this->replyExchange = null;
            $this->replyQueue = null;
        }
    }
}