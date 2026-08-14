<?php

namespace EntelisTeam\Lbaf\Core\Database;

class RabbitWorkerOptions
{
    /**
     * @param int $qos количество задач, которые резервирует worker за раз
     * @param string|null $exchange название обменника, к которому биндится очередь
     * @param array|null $topics темы обменника, на которые биндится очередь // ['#'] = any topic
     */
    public function __construct(
        public int $qos = 1,
        public ?string $exchange = null,
        public ?array $topics = ['#'],
    ) {

    }
}