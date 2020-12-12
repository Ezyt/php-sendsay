<?php

namespace Ezyt\Sendsay\Client;

use Ezyt\Sendsay\Message\MessageInterface;
use GuzzleHttp\Exception\GuzzleException;

interface ClientInterface
{
    /**
     * @param string $action
     * @param array $data
     * @return MessageInterface
     *
     * @throws GuzzleException
     */
    public function request(string $action, array $data = []): MessageInterface;
}
