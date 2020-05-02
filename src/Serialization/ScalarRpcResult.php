<?php

namespace SCode\AmqpRpcTransportBundle\Serialization;

class ScalarRpcResult
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}