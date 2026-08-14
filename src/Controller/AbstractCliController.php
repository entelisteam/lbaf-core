<?php

namespace EntelisTeam\Lbaf\Core\Controller;

use EntelisTeam\Lbaf\Core\Helper\Console;
use EntelisTeam\Lbaf\Core\Response\AbstractResponse;
use EntelisTeam\Lbaf\Core\Response\CliResponse;
use EntelisTeam\Lbaf\Exception\CustomException;
use ReflectionClass;
use Throwable;

abstract class AbstractCliController extends AbstractController
{

    public static function _createResponse(mixed $data): AbstractResponse
    {
        return new CliResponse($data);
    }

    public static function _createErrorResponse(CustomException|Throwable $e): AbstractResponse
    {
        $errorMessage = $e::class . ' ' . $e->getMessage()
            . "\nin " . $e->getFile() . ' line ' . $e->getLine()
            . "\n" . $e->getTraceAsString();

        return new CliResponse(Console::Colorize($errorMessage, 'RED'));
    }

    /**
     * Выводит лог в консоль
     * @param string $text
     * @param string|null $color
     */
    public function log(string $text, string $color = null)
    {
        $text = '[' . date('Y-m-d H:i:s') . ']  ' . $text;
        if (!is_null($color)) {
            $text = Console::Colorize($text, $color);
        }
        echo $text . PHP_EOL;
    }

    /**
     * Cообщить свои методы
     * @return void
     */
    public function getPublicMethods(): array
    {
        $reflection = new ReflectionClass(static::class);
        $methods = $reflection->getMethods();

        $result = [];
        foreach ($methods as $method) {
            if ($method->isPublic() && !$method->isConstructor() && $method->getDeclaringClass()->getName() == static::class) {
                $paramsReflection = $method->getParameters();
                $params = [];
                if (count($paramsReflection)) {
                    foreach ($paramsReflection as $param) {
                        $params[] = ($param->getType() ?? 'mixed') . ' ' . $param->getName();
                    }
                }

                $description = 'none';
                if ($method->getDocComment() !== false) {
                    $lines = explode("\n", $method->getDocComment());
                    if (count($lines) >= 3) {
                        $description = trim($lines[1]);
                    } else {
                        $description = join(' ', $lines);
                    }
                }

                $result[] = (object)[
                    'Method' => $method->getName(),
                    'Arguments' => count($params) ? join(', ', $params) : 'none',
                    'Description' => $description,
                ];
            }
        }

        return $result;
    }
}