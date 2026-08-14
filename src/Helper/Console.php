<?php

namespace EntelisTeam\Lbaf\Core\Helper;

class Console
{

    static function isConsole()
    {
        return defined('STDIN');
    }

    /**
     * Возвращает текст покрашенный в цвет в зависимости от статуса
     * @param string $text Текст для покраски
     * @param string|null $color
     * @return string
     */
    static function Colorize(string $text, string $color = null): string
    {
        switch ($color) {
            case "SUCCESS":
            case "OK":
            case "GREEN":
                $out = "[42m"; //Green background
                break;
            case "FAILURE":
            case "FAIL":
            case "ERROR":
            case "RED":
                $out = "[41m"; //Red background
                break;
            case "WARNING":
            case "WARN":
            case "YELLOW":
                $out = "[43m"; //Yellow background
                break;
            case "NOTE":
            case "BLUE":
                $out = "[44m"; //Blue background
                break;
            default:
                return $text;
                break;
        }

        return chr(27) . "[0m" . chr(27) . $out . $text . chr(27) . "[0m";
    }
}
