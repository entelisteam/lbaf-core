<?php

namespace EntelisTeam\Lbaf\Core\Packer;

use EntelisTeam\Lbaf\Core\Response\Header;

class Json implements PackerInterface
{

    /**
     * @inheritDoc
     */
    public function pack($data): string
    {
        return json_encode($data);
    }

    /**
     * @inheritDoc
     */
    public function unpack(string $data)
    {
        return json_decode($data);
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): array
    {
        return [
            new Header('Content-Type', 'application/json; charset=UTF-8'),
        ];
    }

}
