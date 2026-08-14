<?php

namespace EntelisTeam\Lbaf\Core\Helper;

/**
 * @deprecated
 */
class input
{

    /**
     * Возвращает массив, сформированный из JSON в теле запроса
     * @return mixed
     * @todo что это вообще и зачем
     */
    static function json()
    {
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE);
        return $input;
    }

    /**
     * Возращает значение либо пустую строку
     * @param string $key
     * @param string $def
     * @return string
     */
    static function post($key, $def = null)
    {
        return $_POST[$key] ?? $def;
    }

    /**
     * Получение из массива $_GET значения
     * @param string $key
     * @param string $def
     * @return string
     */
    static function get($key, $def = null)
    {
        return $_GET[$key] ?? $def;
    }

    /**
     * Получение из массива $_COOKIE значения
     * @param string $key
     * @param string $def
     * @return string
     */
    static function cookie($key, $def = null)
    {
        return $_COOKIE[$key] ?? $def;
    }

    /**
     * Получение из массива $_REQUEST значения
     * @param string $key
     * @param string $def
     * @return string
     */
    static function request($key, $def = null)
    {
        return $_REQUEST[$key] ?? $def;
    }

    /**
     * Проверка на post запрос
     * @return bool
     */
    static function isPost()
    {
        return (is_array($_POST) && count($_POST) > 0) || strtolower($_SERVER['REQUEST_METHOD']) == 'post' ? true : false;
    }

    /**
     * Проверка на AJAX запрос
     * @return boolean
     */
    static function isAjax()
    {
        if (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') ||
            (isset($_GET['is_ajax']) && $_GET['is_ajax'])
        ) {
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }
}
