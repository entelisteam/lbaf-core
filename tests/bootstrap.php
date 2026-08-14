<?php

require __DIR__ . '/../vendor/autoload.php';

if (!function_exists('getallheaders')) {
    /**
     * Тесты идут под CLI SAPI, где getallheaders() не существует.
     * Harness симулирует запрос через $_SERVER — отсюда же собираем заголовки.
     *
     * @return array<string, string>
     */
    function getallheaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $headers[str_replace('_', '-', substr($name, 5))] = $value;
            }
        }
        return $headers;
    }
}
