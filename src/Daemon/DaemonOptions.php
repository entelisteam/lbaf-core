<?php

namespace EntelisTeam\Lbaf\Core\Daemon;

use EntelisTeam\Lbaf\Core\Database\RabbitMessageActionEnum;
use Psr\Log\LoggerInterface;

class DaemonOptions
{
    /**
     * @param int $qos Как много сообщений демон должен лочить. Рекомендуется значение от 1 до 100. Уменьшаем если демон медленный, увеличиваем если быстрый.
     * @param RabbitMessageActionEnum $failMode Что делать если обработчик демона вернул false
     * @param RabbitMessageActionEnum $exceptionMode Что делать если обработчик демона кинул Exception
     */
    function __construct(
        public int                     $qos,
        public RabbitMessageActionEnum $failMode,
        public RabbitMessageActionEnum $exceptionMode,
        public ?LoggerInterface $logger = null,
    )
    {
    }

}
