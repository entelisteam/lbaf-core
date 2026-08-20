<?php

namespace EntelisTeam\Lbaf\Core\Logger;

use EntelisTeam\Lbaf\Exception\CustomException;
use EntelisTeam\Lbaf\Exception\LogLevelEnum;
use Psr\Log\LoggerInterface;
use Throwable;

class LogProcessor
{
    /**
     * @throws Throwable
     */
    static function process(?LoggerInterface $logger, Throwable $throwable, array $context = [], ?string $message = null): void
    {

        if ($throwable instanceof CustomException && $throwable->isError === false) {
            //это "ошибка" которая является валидным ответом, ее нужно просто отдать наружу и все.
            //мы не считаем это ошибкой при которой нужно логировать/возбуждаться

            //echo('<pre>'); print_r($throwable::class); echo('</pre>'); //die('!');
            //echo('<pre>'); print_r($throwable->getTraceAsString()); echo('</pre>'); //die('!');

            return;
        }

        if (is_null($logger)) {
            error_log('Exception occurred: ' . $throwable::class . ' ' . $throwable->getMessage()
                . PHP_EOL . "in " . $throwable->getFile() . ' line ' . $throwable->getLine()
                . PHP_EOL . $throwable->getTraceAsString());
            //@todo fixme временное решение чтобы получить ошибку в index.php
            if (defined('STDIN')) {
                throw $throwable;
            }
        } else {
            $logLevel = ($throwable instanceof CustomException) ? $throwable->logLevel->value : LogLevelEnum::Error->value;
            $logger->log(
                $logLevel,
                $message ?: $throwable->getMessage(),
                array_merge(
                    $context,
                    (isset($throwable->context) && is_array($throwable->context) ? $throwable->context : []),
                    ['exception' => $throwable],
                ),
            );
        }
    }

}
