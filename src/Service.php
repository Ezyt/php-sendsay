<?php

namespace Ezyt\Sendsay;

use Ezyt\Sendsay\Client\Client;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use LogicException;

class Service
{
    /** @var Client */
    private $client;

    /**
     * Service constructor.
     * @param array $credentials
     * @param array $options
     * @throws GuzzleException
     * @throws \GuzzleHttp\Exception\InvalidArgumentException
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function __construct(array $credentials, array $options = [])
    {
        $this->client = new Client($credentials, $options);
    }

    /**
     * @param array $credentials
     * @param array $options
     * @return Service
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws \GuzzleHttp\Exception\InvalidArgumentException
     */
    public static function create(array $credentials, array $options = []): Service
    {
        return new static($credentials, $options);
    }

    /**
     * @param string $email
     * @return array|null
     * @throws GuzzleException
     */
    public function getUser(string $email): ?array
    {
        $response = $this->client->request('member.get', ['email' => $email]);
        if ($response->hasError()) {
            return null;
        }
        $data = $response->getData();
        return $data['member'] ?? null;
    }
}
