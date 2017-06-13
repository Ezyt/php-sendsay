<?php

namespace Sendsay\Client;

use GuzzleHttp\Client as HttpClient;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\MessageFormatter;
use Sendsay\Exception\TooManyRedirectsException;
use Sendsay\Message\Message;
use Sendsay\Message\MessageInterface;

class Client implements ClientInterface
{
    const API_VERSION = 100;
    const JSON_RESPONSE = 1;
    const REDIRECT_LIMIT = 10;

    const API_END_POINT = 'https://api.sendsay.ru/';

    /** @var HttpClient */
    protected $httpClient;

    /** @var array */
    protected $credentials = [
        'login'    => null,
        'sublogin' => null,
        'passwd'   => null
    ];

    /** @var string */
    private $session;

    public function __construct($credentials, $options)
    {
        if (!$credentials) {
            throw new \InvalidArgumentException('Invalid api credentials');
        }

        $stack = HandlerStack::create();
        $stack->push(
            Middleware::log(
                new Logger('api.sendsay', [
                    new StreamHandler($options['log.path'], Logger::INFO)
                ]),
                new MessageFormatter('{req_body} - {res_body}')
            )
        );

        $this->credentials = $credentials;
        $this->login();
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function login()
    {
        if (!isset($this->session)) {
            $message = $this->request('login', $this->credentials);
            $data = $message->getData();
            if (!isset($data['session'])) {
                throw new \InvalidArgumentException($message->getError());
            }
            $this->session = $data['session'];
        }
    }

    /**
     * @param string $action
     * @param array $data
     * @return MessageInterface
     *
     * @throws TooManyRedirectsException
     */
    public function request($action, $data = [])
    {
        $message = new Message();

        try {
            $data['action'] = $action;

            $params = $this->buildRequestParams($data);

            $redirectCount = 0;
            $redirectPath = '';

            do {
                $response = $this->sendRequest(self::API_END_POINT . $redirectPath, $params);
                if (isset($response['REDIRECT'])) {
                    $redirectPath = $response['REDIRECT'];
                }
                $redirectCount++;
                if ($redirectCount > self::REDIRECT_LIMIT) {
                    throw new TooManyRedirectsException('Too many redirects');
                }
            } while (isset($response['REDIRECT']));

            if (isset($response['errors'])) {
                $errorMessage = $this->getErrorMessageFromResponse($response);
                return $message->setError($errorMessage);
            }

            $message->setData(isset($response['obj']) ? $response['obj'] : $response);
        } catch (\Exception $e) {
            $message->setError($e->getMessage());
        }
        return $message;
    }

    /**
     * @param array $response
     * @return string
     */
    private function getErrorMessageFromResponse($response)
    {
        $error = reset($response['errors']);
        if (!isset($error['explain'])) {
            return $error['id'];
        }
        return is_string($error['explain']) ? $error['explain'] : serialize($error['explain']);
    }

    /**
     * @param string $url
     * @param array $params
     * @return array|null
     */
    private function sendRequest($url, $params = [])
    {
        $response = $this->httpClient->post($url, [
            'verify'      => false,
            'form_params' => $params
        ]);

        return json_decode((string)$response->getBody(), true, 512, 0);
    }

    /**
     * @param array $data
     * @return array
     */
    private function buildRequestParams($data)
    {
        if ($this->session !== null && !isset($data['session'])) {
            $data['session'] = $this->session;
        }

        return [
            'apiversion' => self::API_VERSION,
            'json'       => self::JSON_RESPONSE,
            'request.id' => mt_rand(100, 999),
            'request'    => json_encode($data),
        ];
    }
}