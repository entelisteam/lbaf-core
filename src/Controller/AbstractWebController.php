<?php

namespace EntelisTeam\Lbaf\Core\Controller;

use EntelisTeam\Lbaf\Core\Response\AbstractResponse;
use EntelisTeam\Lbaf\Core\Response\WebResponse;
use EntelisTeam\Lbaf\Exception\CustomException;
use Throwable;

/**
 * Контроллер для показа web
 * @todo сейчас display_var получается недоступно в api/cli что не очень логично, возможно стоит их вынести в trait
 * @todo есть дублирующийся функционал с render
 */
abstract class AbstractWebController extends AbstractController
{

    public static function _createResponse(mixed $data): AbstractResponse
    {
        return new WebResponse($data, 200);
    }

    public static function _createErrorResponse(CustomException|Throwable $e): AbstractResponse
    {
        return new WebResponse(
            '<pre>' . $e->getMessage() . PHP_EOL .
            'in ' . $e->getFile() . ' line ' . $e->getLine() . PHP_EOL .
            $e->getTraceAsString() . '</pre>',
            ($e instanceof CustomException) ? $e->getCode() : 500
        );
    }

    /**
     * Рендерит шаблон в переменную
     * @param string $template шаблон для показа
     * @param array $params параметры для передачи в шаблон
     * @return string
     * @todo перенести куда-то выше и/или перейти на шаблоны
     */
    protected final function display_var(string $template, array $params = []): string
    {
        ob_start();

        $display = function () use ($params, $template) {
            extract($params, EXTR_SKIP);
            require('view/' . $template . '.php');
        };
        $display();

        $out = ob_get_contents();

        ob_end_clean();
        return $out;
    }

}
