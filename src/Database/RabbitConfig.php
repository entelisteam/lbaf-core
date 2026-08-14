<?php

namespace EntelisTeam\Lbaf\Core\Database;

class RabbitConfig
{
    public function __construct(
        public string $host,
        public string $port,
        public string $user,
        public string $password,
        public string $vhost = '/')
    {
    }

}
