<?php

namespace Tests\integration\App\Packer;

use EntelisTeam\Lbaf\Core\Packer\PackerInterface;

/**
 * Не-дефолтный упаковщик — нужен, чтобы проверить, что аргумент packer действительно используется,
 * а не подменяется значением по умолчанию (Json).
 */
class Base64Json implements PackerInterface
{
    public function pack($data): string
    {
        return base64_encode(json_encode($data));
    }

    public function unpack(string $data)
    {
        return json_decode(base64_decode($data));
    }

    public function getHeaders(): array
    {
        return [];
    }
}
