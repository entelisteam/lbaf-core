<?php

namespace EntelisTeam\Lbaf\Core\Helper;

use Closure;
use EntelisTeam\Lbaf\ConsoleTable\ConsoleTable;

class Timer
{
    static $milestones;

    /**
     * Отображает накопленные данные в виде html таблицы
     * @param bool $return
     * @return string|void
     */
    static function result(bool $return = false)
    {
        $output = defined('STDIN') ? self::getResultConsole() : self::getResultHtmlTable();
        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }

    /**
     * Отображает накопленные данные в виде консольной таблицы
     * @return string
     * @uses \Lbaf\Helper\ConsoleTable
     */
    static function getResultConsole(): string
    {
        return ConsoleTable::fromRows(self::getData());
    }

    /**
     * Генерирует структуру с данными
     * @return array
     */
    static protected function getData(): array
    {
        $milestones = self::$milestones;
        $milestones[] = ['FINISH', self::getMicrotime()]; //некоторое дублирование кода с self::click

        $result = [];
        foreach ($milestones as $elem => $data) {
            $result[] = (object)[
                'Milestone' => $data[0],
                'Diff' => round(($elem ? $data[1] - self::$milestones[$elem - 1][1] : '0'), 4),
                'Cumulative' => round(($data[1] - self::$milestones[0][1]), 4),
            ];
        }
        return $result;
    }

    static public function getMicrotime(): float
    {
        return microtime(true);
    }

    /**
     * Генерирует html таблицу с данными
     * @return string
     */
    static protected function getResultHtmlTable(): string
    {
        $output = '';
        $data = self::getData();
        if (count($data)) {
            $keys = array_keys((array)reset($data));
            $output .= '<table border="1"><tr>';
            foreach ($keys as $key) {
                $output .= '<th>' . $key . '</th>';
            }
            $output .= '</tr>';
            foreach ($data as $item) {
                $output .= '<tr>';
                foreach ($item as $value) {
                    $output .= '<td>' . $value . '</td>';
                }
                $output .= '</tr>';
            }
            $output .= '</table>';
        }
        return $output;
    }

    /**
     * Adds event to timer
     * @param string $name Event name
     * @return void
     */
    static public function click(string $name): void
    {
        self::$milestones[] = [$name, self::getMicrotime()];
    }

    /**
     * Возвращает данные и время выполнения в ms в стиле go.
     * [$result, $timeMs] = go(...)
     * @param Closure $dataProvider функция для обработки
     * @return array <0: mixed, 1: float>
     */
    static function go(Closure $dataProvider): array
    {
        $start = self::getMicrotime();
        $data = $dataProvider();
        $generateTime = (1000 * (self::getMicrotime() - $start));
        return [$data, $generateTime];
    }

}