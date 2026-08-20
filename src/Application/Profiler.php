<?php

namespace EntelisTeam\Lbaf\Core\Application;

use Closure;
use EntelisTeam\Lbaf\ConsoleTable\ConsoleTable;
use EntelisTeam\Lbaf\Core\Helper\Console;
use EntelisTeam\Lbaf\Core\Helper\debug;
use EntelisTeam\Lbaf\Core\Helper\Timer;
use Exception;
use ReflectionMethod;
use Throwable;

/**
 * Класс отвечающий за профилирование кода с учетом вложенности объектов
 * @todo убрать двусмысленности в названиях
 *      Уже не помню как и зачем я это писал, но видимо
 *      run             - когда просто вызывается какой-то кусок логики внутри текущей функции
 *      runObjectMethod - когда мы вызываем функцию какую-то другую - вот это нужно видимо вообще убрать за счет вставки profiler в ModelPrototype
 *
 * @todo написать понятные комментарии и примеры использования
 *
 * @todo реализовать возможность глобально отключать всю эту логику - наверное все-таки на этом уровне, т.к вызовы могут быть накиданы по коду руками
 *
 * @todo реализовать сохранение данных (куда угодно)
 *
 * @deprecated @todo remove it
 */
class Profiler
{
    static $items;

    /**
     * Вызов куска кода внутри текущей функции
     * @param $description Описание функциональности
     * @param Closure $function Анонимная фунция для вызова
     * @return mixed
     * @throws Exception
     * @todo подумать - нужно ли тут вообще вытаскивать parent - т.к это вложенный функционал, он всегда внутри чего-то будет
     */
    static function run(string $description, Closure $function)
    {
        $parent = debug::getCaller();
        return self::_run(
            $parent['class'] ?? '',
            ($parent['function'] ?? '') . '::' . $description,
            $function
        );
    }

    private static function _run($className, $functionName, Closure $function)
    {
        self::start($className, $functionName);
        $e = null;
        try {
            $result = $function();
        } catch (Throwable $e) {

        }
        self::end($className, $functionName);
        if (!empty($e)) {
            throw $e;
        }
        return $result;
    }

    private static function start($class, $function)
    {
        self::event($class, $function, 'start');
    }

    private static function event($class, $function, $type)
    {
        self::$items[] = [
            'class' => $class,
            'function' => $function,
            'type' => $type,
            'time' => Timer::getMicrotime()
        ];
    }

    private static function end($class, $function)
    {
        #usleep(mt_rand(0,100000)); //for testing
        self::event($class, $function, 'end');
        #usleep(mt_rand(0,1000));
    }

    /**
     * @deprecated надо удалить
     * Сейчас используется в ControllerProxy для вызова action контроллера. Нужно придумать что-то вместо
     * @todo подумать над синтаксисом profiler::_(...)
     * @todo подумать над спрятать $object, $method в один аргумент-массив вида [$object,$method] для прозрачного вызова call_user_func_array над всякой статикой итд
     * @todo была идея сделать model prototype (на самом деле model_proxy и обернуть все вызовы модели в это дело для дефолтной отладки
     */
    static function runObjectMethod($object, $method, $params = [])
    {
        $r = new ReflectionMethod($object, $method);
        return self::_run($r->class, $r->name, function () use (&$object, &$method, &$params) {
            return call_user_func_array([$object, $method], $params); //начиная с php 8.0 по умолчанию $params трактуются как named arguments
        });
    }

    /**
     * Альтернативный вызов
     * @deprecated надо удалить
     * @todo подумать как правильнее
     */
    static function runObjectMethod2(array $objectMethod, $params = [])
    {
        return self::_run($objectMethod[0]::class, $objectMethod[1], function () use (&$objectMethod, &$params) {
            return call_user_func_array($objectMethod, $params);
        });
    }

    static function print()
    {
        if (!Console::isConsole()) {
            echo self::result(true);
        } else {
            echo ConsoleTable::fromRows(self::get_flat_table(self::get_data()));
        }
    }

    static function result(bool $return = false)
    {

        $output = self::get_html_table(self::get_data());
        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }

    static private function get_html_table($data, $printHeader = true)
    {
        $output = '';
        if (count($data)) {
            $output .= '<table border="1" style="font-face:courier"><tr>';
            if ($printHeader) {
                $keys = array_keys((array)reset($data));
                foreach ($keys as $key) {
                    if ($key != '_sub') {
                        $output .= '<th>' . $key . '</th>';
                    }
                }
            }

            $output .= '</tr>';
            foreach ($data as $item) {
                $output .= '<tr>';
                foreach ($item as $value) {
                    if (!is_array($value)) {
                        $output .= '<td>' . $value . '</td>';
                    }
                }
                $output .= '</tr>';
                if (isset($item['_sub'])) {
                    $output .= '<tr><td></td><td colspan="' . (count($item) - 2) . '">';
                    $output .= self::get_html_table($item['_sub'], false);
                    $output .= '</td></tr>';
                }
            }
            $output .= '</table>';
        }
        return $output;
    }

    /**
     * Преобразует сырые данные лога в читаемый вид
     */
    static function get_data()
    {
        //нужно построить из плоской структуры дерево
        return self::getTree(self::$items);
    }

    static private function getTree(&$items)
    {
        $result = [];
        $level = 0;
        $tmp = [];

        #echo('<br><br><br><pre>CALLED'); print_r($items); echo('</pre>'); //die('!');
        foreach ($items as $item) {
            if ($item['type'] == 'start') {
                $level++;
            } elseif ($item['type'] == 'end') {
                $level--;
            }
            #echo('===========<br><pre>'); print_r($item); echo('</pre>'); //die('!');
            #echo('<pre>'); print_r($level); echo('</pre>==========<br>'); //die('!');

            //level == 1 - открытие текущего
            //level == 0 - закрытие текущего
            //level > 1 - вложенные истории
            if ($level == 1 && $item['type'] == 'start') {
                $statItem = [
                    'class' => $item['class'],
                    'function' => $item['function'],
                    'time' => $item['time'],
                ];
            }
            if ($level == 0 && $item['type'] == 'end') {
                if (count($tmp)) {
                    $statItem['_sub'] = self::getTree($tmp);
                    $tmp = [];
                }
                $statItem['diff'] = number_format(round($item['time'] - $statItem['time'], 3), 3);
                $statItem['total'] = number_format(round($item['time'] - $items[0]['time'], 3), 3);
                unset($statItem['time']);
                $result[] = $statItem;
            }
            if ($level > 1) {
                $tmp[] = $item;
            }
            if ($level == 1 && $item['type'] == 'end') {
                $tmp[] = $item;
            }
        }

        return $result;
    }

    //@todo переделать, как-то не красиво

    /**
     * Функция возвращает плоскую структуру
     */
    static private function get_flat_table($data, $level = 0)
    {
        $output = [];

        if (count($data)) {
            foreach ($data as $item) {

                $tmp = $item;
                if (isset($tmp['_sub'])) {
                    unset($tmp['_sub']);
                }

                foreach ($tmp as $key => $tmp_item) {
                    #if (!is_numeric($tmp_item)) {
                    $tmp[$key] = str_pad('', $level, '|') . $tmp_item;
                    #}
                }

                $output[] = $tmp;


                if (isset($item['_sub'])) {
                    $sub = self::get_flat_table($item['_sub'], ($level + 1));
                    foreach ($sub as $tmp_item) {
                        $output[] = $tmp_item;
                    }
                }
            }
        }
        return $output;
    }


}
