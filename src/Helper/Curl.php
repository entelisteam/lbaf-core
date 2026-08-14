<?php

namespace EntelisTeam\Lbaf\Core\Helper;

use Exception;
use Throwable;

/**
 * @todo вычистить грязь, вынести хардкоды в переменные, в целом проверить актуальность кода
 */
class Curl
{
    public static string $httpHeaders;
    public static int $responseCode;
    public static array $cookieList;

    public static function get(string $url, array $getParameters = [], int $connectTimeout = 90, array $headers = [], array $curlOptions = [])
    {
        return self::query(
            url: self::generateUrl($url, $getParameters),
            method: 'GET',
            connectTimeout: $connectTimeout,
            headers: $headers,
            curlOptions: $curlOptions,
        );
    }

    private static function generateUrl(string $url, array $getParameters): string
    {
        return $url . (count($getParameters) ? '?' . http_build_query($getParameters) : '');
    }

    /**
     * Отправка запроса
     * @param string $url адрес включая get параметры
     * @param string $method тип запроса строкой GET/POST/DELETE
     * @param array $postParameters key=>value массив данных для POST отправки
     * @param integer $connectTimeout лимит времени на запрос
     * @param array $headers дополнительные заголовки запроса
     * @param array $curlOptions дополнительные параметры CURL
     * @return bool|mixed|string
     * @throws Exception
     */
    public static function query(string $url, string $method = 'GET', array $postParameters = [], int $connectTimeout = 90, array $headers = [], array $curlOptions = [])
    {
        //create cURL connection
        $curlConnection = curl_init($url);

        if (is_null($headers)) {
            $headers = [];
        }

        // Выбираем тип запроса
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($curlConnection, CURLOPT_POST, true);
                break;
            case 'GET':
                curl_setopt($curlConnection, CURLOPT_POST, false);
                break;
            case 'PUT':
                curl_setopt($curlConnection, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
            case 'PATCH':
                curl_setopt($curlConnection, CURLOPT_CUSTOMREQUEST, 'PATCH');
                break;
            case 'HEAD':
                curl_setopt($curlConnection, CURLOPT_CUSTOMREQUEST, 'HEAD');
                break;
            case 'DELETE':
                curl_setopt($curlConnection, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $defaultCurlOptions = [
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_TIMEOUT => $connectTimeout,
            CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_URL => $url,
        ];
        $options = array_replace_recursive($defaultCurlOptions, $curlOptions);
        foreach ($options as $key => $value) {
            curl_setopt($curlConnection, $key, $value);
        }


        // собираем пост строку
        $postString = '';
        if (count($postParameters)) {
            if (isset($postParameters[''])) {
                $postString = $postParameters['']; // hack для передачи в Body
            } else {
                $postString = http_build_query($postParameters);
            }
        }

        //set data to be posted
        if ($postString != '') {
            curl_setopt($curlConnection, CURLOPT_POSTFIELDS, $postString);
            $contentLength = mb_strlen($postString, '8bit');
        } else {
            $contentLength = 0;
        }
        $headers[] = 'Content-Length: ' . $contentLength;

        curl_setopt($curlConnection, CURLOPT_HTTPHEADER, (array)$headers);
        curl_setopt($curlConnection, CURLOPT_HEADER, true);

        //perform our request
        try {
            $result = curl_exec($curlConnection);
            if ($result === false) {
                $message = curl_errno($curlConnection) . ': ' . curl_error($curlConnection);
                throw new Exception($message);
            }

            self::$cookieList   = curl_getinfo ($curlConnection, CURLINFO_COOKIELIST); //http://php.net/manual/ru/function.curl-getinfo.php //php 5.5+
            self::$responseCode = curl_getinfo($curlConnection, CURLINFO_HTTP_CODE);
            self::$httpHeaders = substr($result, 0, curl_getinfo($curlConnection, CURLINFO_HEADER_SIZE));

            $result = substr($result, curl_getinfo($curlConnection, CURLINFO_HEADER_SIZE));

        } catch (Throwable $e) {
            throw $e;
        }
        //close the connection
        curl_close($curlConnection);

        return $result;
    }

    public static function post(string $url, array $postParameters = [], array $getParameters = [], int $connectTimeout = 90, array $headers = [], array $curlOptions = [])
    {
        return self::query(
            url: self::generateUrl($url, $getParameters),
            method: 'POST',
            postParameters: $postParameters,
            connectTimeout: $connectTimeout,
            headers: $headers,
            curlOptions: $curlOptions,
        );
    }


    public static function patch(string $url, array $postParameters = [], array $getParameters = [], int $connectTimeout = 90, array $headers = [], array $curlOptions = [])
    {
        return self::query(
            url: self::generateUrl($url, $getParameters),
            method: 'PATCH',
            postParameters: $postParameters,
            connectTimeout: $connectTimeout,
            headers: $headers,
            curlOptions: $curlOptions,
        );
    }

    public static function put(string $url, array $postParameters = [], array $getParameters = [], int $connectTimeout = 90, array $headers = [], array $curlOptions = [])
    {
        return self::query(
            url: self::generateUrl($url, $getParameters),
            method: 'PUT',
            postParameters: $postParameters,
            connectTimeout: $connectTimeout,
            headers: $headers,
            curlOptions: $curlOptions,
        );
    }

    public static function delete(string $url, array $postParameters = [], array $getParameters = [], int $connectTimeout = 90, array $headers = [], array $curlOptions = [])
    {
        return self::query(
            url: self::generateUrl($url, $getParameters),
            method: 'DELETE',
            postParameters: $postParameters,
            connectTimeout: $connectTimeout,
            headers: $headers,
            curlOptions: $curlOptions,
        );
    }

    public static function head(string $url, array $postParameters = [], array $getParameters = [], int $connectTimeout = 90, array $headers = [], array $curlOptions = [])
    {
        return self::query(
            url: self::generateUrl($url, $getParameters),
            method: 'HEAD',
            postParameters: $postParameters,
            connectTimeout: $connectTimeout,
            headers: $headers,
            curlOptions: $curlOptions,
        );
    }

}
