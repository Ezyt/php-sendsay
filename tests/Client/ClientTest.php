<?php

namespace Ezyt\Sendsay\Tests\Client;

use Ezyt\Sendsay\Client\Client;
use Ezyt\Sendsay\Tests\BaseTestCase;
use InvalidArgumentException;

class ClientTest extends BaseTestCase
{
    /**
     * @covers
     * @throws InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \GuzzleHttp\Exception\InvalidArgumentException
     * @throws \LogicException
     */
    public function testConstructorWithInvalidCredentials(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Client([], ['log.path' => 'api.sendsay.log']);
    }

    /**
     * @covers
     * @throws InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \GuzzleHttp\Exception\InvalidArgumentException
     * @throws \LogicException
     */
    public function testConstructor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Client(
            [
                'login'    => 'foo',
                'sublogin' => 'bar',
                'passwd'   => 'test',
            ],
            [
                'log.path' => __DIR__ . '/../../logs/api.sendsay.log',
            ]
        );
    }
}
