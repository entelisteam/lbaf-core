<?php

namespace EntelisTeam\Lbaf\Core\Helper;

use Closure;
use Exception;

/**
 * Класс для тестирования производительности кода
 * @deprecated
 * @todo вынести в отдельный Package, это явно не часть Lbaf, хоть и зависит от него
 */
class PerformanceTester
{

    /**
     * @todo описать аргументы
     */
    public static function runTests(array $testFunctions, int $iterations = 1, $expected = null): array
    {
        $result = [];

        foreach ($testFunctions as $testTitle => $testFunction) {
            $result[] = [
                'Title' => $testTitle,
                'Time' => self::runTest($testFunction, $iterations, $expected),
            ];
        }

        //process Delta
        $bestTime = reset($result)['Time'];
        foreach ($result as $item) {
            if ($bestTime > $item['Time']) {
                $bestTime = $item['Time'];
            }
        }

        foreach ($result as &$item) {
            $delta = $item['Time'] - $bestTime;
            $item['Delta'] = $delta != 0 ? $delta : null;
            $item['Delta %'] = ($delta != 0) ? (100 * $delta / $bestTime) : null;
        }
        return $result;
    }

    /**
     * @param Closure $testFunction тестируемая функция
     * @param int $iterations Количество итераций
     * @param ?mixed $expected Ожидаемый результат
     * @return float Время выполнения теста в секундах
     * @throws Exception
     */
    public static function runTest(Closure $testFunction, int $iterations = 1, $expected = null): float
    {
        $start = Timer::getMicrotime();

        for ($i = 0; $i < $iterations; $i++) {
            $result = $testFunction();
            if (isset($expected) && $result != $expected) {
                throw new Exception('unexpected result');
            }
        }
        $end = Timer::getMicrotime();

        return ($end - $start);
    }


}