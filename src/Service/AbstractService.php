<?php

namespace EntelisTeam\Lbaf\Core\Service;

use EntelisTeam\Lbaf\Core\Helper\Curl;
use EntelisTeam\Lbaf\Exception\InnerServiceException;
use EntelisTeam\Lbaf\Exception\UnexpectedException;

/**
 * @deprecated мне очень не нравится идея этого класса как части lbaf
 */
abstract class AbstractService
{

    /**
     * @param string $baseUrl Базовый url куда слать запросы
     * @param array $headers Заголовки которые будут добавлены к каждому запросу
     * @param array $curlOptions Параметры curl которые будут переопределены в каждом запросе
     * @todo возможно стоит перейти на явное получение curl объекта (совсем хорошо если psr совместимого)
     */
    public function __construct(protected string $baseUrl, protected int $timeout = 90, protected array $headers = [], protected array $curlOptions = [])
    {
    }

    protected function get(string $endPoint, array $getParameters = []): mixed
    {
        $response = Curl::get(
            url: $this->baseUrl . $endPoint,
            getParameters: $getParameters,
            connectTimeout: $this->timeout,
            headers: $this->headers,
            curlOptions: $this->curlOptions,
        );

        return $this->processResponse($response, $endPoint, $getParameters);
    }

    private function processResponse(string $response, string $endPoint, array $getParameters = [], array $postParameters = []): mixed
    {
        //@todo use Lbaf\Response\Packer\PackerInterface::unpack
        $json = json_decode($response, true);
        if (isset($json['error'])) {
            $err = new InnerServiceException(
                message: $json['error']['error_message'],
                httpCode: $json['error']['code'],
                customCode: $json['error']['error_code'],
            );
            $err->context = [
                'Connector' => static::class,
                'BaseUrl' => $this->baseUrl,
                'EndPoint' => $endPoint,
                'Response' => $response,
            ];
            throw $err;
        }

        if (!$json || !array_key_exists('data', $json)) {
            $err = new UnexpectedException(sprintf(join(PHP_EOL, [
                "Received unexpected response format from service <%s>",
                "EndPoint:<%s>"
            ]), $this->baseUrl, $endPoint));
            $err->context = [
                'Connector' => static::class,
                'BaseUrl' => $this->baseUrl,
                'EndPoint' => $endPoint,
                'Response' => $response,
            ];

            throw $err;
        }

        return $json['data'];
    }

    protected function post(string $endPoint, array $postParameters = [], array $getParameters = []): mixed
    {
        $json = Curl::post(
            url: $this->baseUrl . $endPoint,
            postParameters: $postParameters,
            getParameters: $getParameters,
            connectTimeout: $this->timeout,
            headers: $this->headers,
            curlOptions: $this->curlOptions
        );
        return $this->processResponse($json, $endPoint, $getParameters, $postParameters);
    }
}
