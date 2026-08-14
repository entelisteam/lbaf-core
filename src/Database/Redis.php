<?php

namespace EntelisTeam\Lbaf\Core\Database;

class Redis
{
    private ?\Redis $client = null;
    private RedisConfig $config;

    public function __construct(RedisConfig $config)
    {
        $this->config = $config;
        $this->client = new \Redis();

        $this->connect();
        $this->client->setOption(\Redis::OPT_MAX_RETRIES, $config->retryCount);
    }

    public function ping(): void
    {
        try {
            if ($this->client?->ping() !== true) {
                $this->connect();
            }
        } catch (\Throwable $e) {
            $this->connect();
        }
    }

    private function connect(): void
    {

        $context = (is_null($this->config->tlsVerifyPeer)) ? [] : [
            'stream' =>
                [
                    'verify_peer_name' => $this->config->tlsVerifyPeer,
                    'verify_peer' => $this->config->tlsVerifyPeer
                ]
        ];

        if ($this->config->persistentConnections) {
            $this->client->pconnect(
                $this->config->host,
                $this->config->port,
                $this->config->timeout,
                null,
                $this->config->retryInterval,
                $this->config->timeoutRead,
                $context
            );
        } else {
            $this->client->connect(
                $this->config->host,
                $this->config->port,

                $this->config->timeout,
                null,
                $this->config->retryInterval,
                $this->config->timeoutRead,
                $context
            );
        }

        if ($this->config->password) {
            $auth = ['pass' => $this->config->password];
            if ($this->config->user) {
                $auth['user'] = $this->config->user;
            }
            $this->client->auth($auth);
        }

        $this->client->select($this->config->database);
    }

    /**
     * @param string $key
     * @param string $value
     * @param int|null $ttl The key's remaining Time To Live, in seconds
     */
    public function set(string $key, string $value, ?int $ttl = null): ?string
    {
        return $this->client->set($key, $value, $ttl);
    }

    public function get(string $key): ?string
    {
        return $this->client->get($key);
    }

    public function setEx(string $key, int $ttl, string $value): void
    {
        $this->client->setex($key, $ttl, $value);
    }

    public function del(string $key): void
    {
        $this->client->del($key);
    }

    public function hGetAll(string $key): array
    {
        return $this->client->hGetAll($key);
    }

    public function hMGet(string $key, array $hashKeys): array
    {
        return $this->client->hMGet($key, $hashKeys);
    }

    /**
     * @param int|null $ttl The key's remaining Time To Live, in seconds
     * @todo refactor with hSet, as method is deprecated in Redis
     */
    public function hMSet(string $key, array $hashKeys, ?int $ttl = null): void
    {
        $this->client->hMSet($key, $hashKeys);
        if ($ttl) {
            $this->expire($key, $ttl);
        }
    }

    /**
     * Sets an expiration date (a timeout) on an item
     * @param string $key The key that will disappear
     * @param int|null $ttl The key's remaining Time To Live, in seconds
     */
    public function expire(string $key, int $ttl): void
    {
        $this->client->expire($key, $ttl);
    }

    /**
     * Sets the specified fields to their respective values in the hash stored at key.
     * @link https://redis.io/commands/hset/
     * @param string $key
     * @param string $hashKey
     * @param string $value
     * @param int|null $ttl The key's remaining Time To Live, in seconds
     */
    public function hSet(string $key, string $hashKey, string $value, ?int $ttl = null): void
    {
        $this->client->hSet($key, $hashKey, $value);
        if ($ttl) {
            $this->expire($key, $ttl);
        }
    }

    public function hDel(string $key, string $hashKey): void
    {
        $this->client->hDel($key, $hashKey);
    }

    public function sAdd(string $key, string ...$values): void
    {
        $this->client->sAdd($key, ...$values);
    }

    public function sRem(string $key, string ...$values): void
    {
        $this->client->sRem($key, ...$values);
    }

    public function sMembers(string $key): array
    {
        return $this->client->sMembers($key);
    }
}
