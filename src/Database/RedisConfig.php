<?php

namespace EntelisTeam\Lbaf\Core\Database;

class RedisConfig
{

    public bool $persistentConnections = false;

    /**
     * @var float value in seconds (0 meaning unlimited)
     */
    public float $timeout = 1;

    /**
     * @var float value in seconds (0 meaning unlimited)
     */
    public float $timeoutRead = 1;

    /**
     * @var int retry interval in milliseconds
     */
    public int $retryInterval = 100; //ms

    public int $retryCount = 10;

    public ?bool $tlsVerifyPeer = null; //do not specify

    public function __construct(
        public string $host,
        public string $user,
        public string $password,
        public string $database,
        public int    $port = 6379
    )
    {
    }
}
