<?php

namespace SCode\AmqpRpcTransportBundle\Transport;

use Symfony\Component\Messenger\Exception\TransportException;
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

    public function reply(string $replyTo, string $body, array $headers = []): void
    {
        $this->clearWhenDisconnected();
        
        if (null === $this->replyExchange) {
            $this->replyExchange = new \AMQPExchange($this->original->channel());
        }

        $attributes = ['headers' => $headers];

        if (isset($headers['Content-Type'])) {
            $attributes['content_type'] = $headers['Content-Type'];
            unset($attributes['headers']['Content-Type']);
        }

        $this->replyExchange->publish($body, $replyTo, AMQP_NOPARAM, $attributes);
    }
    
    public function get(): \AMQPEnvelope
    {
        $response = null;

        $this->getReplyQueue()->consume(function (\AMQPEnvelope $envelope) use (&$response) {
            $response = $envelope;

            return false;
        }, AMQP_JUST_CONSUME);

        if ($response === null) {
            throw new TransportException('Unable to get rpc response');
        }

        return $response;
    }
    
    public function getWrapped(): Connection
    {
        return $this->original;
    }

    public function initReplyQueue(): string
    {
        $this->clearWhenDisconnected();

        if (null === $this->replyQueue) {
            $this->replyQueue = new \AMQPQueue($this->original->channel());
            $this->replyQueue->setName(self::DIRECT_REPLY_QUEUE);
            $this->replyQueue->consume(null, AMQP_AUTOACK);
        }

        return self::DIRECT_REPLY_QUEUE;
    }

    private function getReplyQueue(): \AMQPQueue
    {
        if ($this->replyQueue === null) {
            throw new \LogicException('Reply queue should be initialized first');
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