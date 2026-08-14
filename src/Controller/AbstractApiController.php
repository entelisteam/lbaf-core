<?php

namespace EntelisTeam\Lbaf\Core\Controller;

use EntelisTeam\Lbaf\Core\Packer\Json;
use EntelisTeam\Lbaf\Core\Response\AbstractResponse;
use EntelisTeam\Lbaf\Core\Response\ApiResponse;
use EntelisTeam\Lbaf\Exception\CustomException;
use Throwable;

//@todo нужно рефакторить и сильно
abstract class AbstractApiController extends AbstractController
{

    public static function _createResponse(mixed $data): AbstractResponse
    {
        return new ApiResponse(new Json(), $data);
    }

    public static function _createErrorResponse(CustomException|Throwable $e): AbstractResponse
    {

        //в CustomException и потомках в code ошибки хранится http код
        $code = ($e instanceof CustomException) ? $e->getCode() : 500;
        return new ApiResponse(new Json(), (object)[
            'error' => (object)[
                'code' => (int)$code,
                'error_message' => $e->getMessage(),
                'error_code' => ($e instanceof CustomException) ? $e->getCustomCode() : 0,
                'info_get' => $_GET,
                'info_post' => $_POST,
                'info_files' => $_FILES,
                //'info_server' => $_SERVER,
            ],
        ], $code);
    }
}
