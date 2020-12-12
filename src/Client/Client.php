<?php

namespace Ezyt\Sendsay\Client;

use Exception;
use Ezyt\Sendsay\Exception\TooManyRedirectsException;
use Ezyt\Sendsay\Message\Message;
use Ezyt\Sendsay\Message\MessageInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use InvalidArgumentException;
use LogicException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

use function is_string;

class Client implements ClientInterface
{
    protected const    API_VERSION    = 100;
    protected const    JSON_RESPONSE  = 1;
    protected const    REDIRECT_LIMIT = 10;

    protected const API_END_POINT = 'https://api.sendsay.ru/';

    /** @var HttpClient */
    protected $httpClient;

    /** @var array */
    protected $credentials = [
        'login'    => null,
        'sublogin' => null,
        'passwd'   => null,
    ];

    /** @var string */
    private $session;

    /**
     * Client constructor.
     * @param $credentials
     * @param $options
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws \GuzzleHttp\Exception\InvalidArgumentException
     * @throws LogicException
     */
    public function __construct(array $credentials, array $options)
    {
        if (empty($credentials)) {
            throw new InvalidArgumentException('Invalid api credentials');
        }

        $stack = HandlerStack::create();
        $stack->push(
            Middleware::log(
                new Logger(
                    'api.sendsay',
                    [
                        new StreamHandler($options['log.path'], Logger::INFO),
                    ]
                ),
                new MessageFormatter('{req_body} - {res_body}')
            )
        );

        $this->httpClient = new HttpClient(['handler' => $stack]);
        $this->credentials = $credentials;
        $this->login();
    }

    /**
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    private function login(): void
    {
        if ($this->session === null) {
            $message = $this->request('login', $this->credentials);
            $data = $message->getData();
            if (!isset($data['session'])) {
                throw new InvalidArgumentException($message->getError());
            }
            $this->session = $data['session'];
        }
    }

    /**
     * @param string $action
     * @param array $data
     * @return MessageInterface
     *
     * @throws GuzzleException
     */
    public function request(string $action, array $data = []): MessageInterface
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

            $message->setData($response['obj'] ?? $response);
        } catch (Exception $e) {
            $message->setError($e->getMessage());
        }
        return $message;
    }

    private function getErrorMessageFromResponse(array $response): string
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
     * @throws GuzzleException
     */
    private function sendRequest(string $url, array $params = []): ?array
    {
        $response = $this->httpClient->post(
            $url,
            [
                'verify'      => false,
                'form_params' => $params,
            ]
        );

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    private function buildRequestParams(array $data): array
    {
        if ($this->session !== null && !isset($data['session'])) {
            $data['session'] = $this->session;
        }

        return [
            'apiversion' => self::API_VERSION,
            'json'       => self::JSON_RESPONSE,
            'request.id' => random_int(100, 999),
            'request'    => json_encode($data),
        ];
    }
}
