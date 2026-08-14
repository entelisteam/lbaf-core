<?php

/**
 * Код отвечает за доступ к конфигу приложения
 * @todo подумать нужен ли он вообще. По хорошему это не должно быть доступно вне definitions Container
 */

namespace EntelisTeam\Lbaf\Core\Config;

class Config
{
    private static $config = null;

    static function get()
    {
        return self::$config;
    }

    static function load(string $path): void
    {
        self::$config = require $path;
    }

    static function loadObject (\stdClass $object): void
    {
        self::$config = $object;
    }

    static function loadClassName (string $className): void
    {
        self::$config = new $className();
    }

}
