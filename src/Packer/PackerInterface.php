<?php

namespace EntelisTeam\Lbaf\Core\Packer;

use EntelisTeam\Lbaf\Core\Response\Header;

interface PackerInterface
{

    /**
     * @param mixed $data
     * @return string
     */
    public function pack($data): string;

    /**
     * @param string $data
     * @return mixed
     */
    public function unpack(string $data);

    /**
     * @return Header[] массив заголовок
     */
    public function getHeaders(): array;

    public function getType(): PackerType;
}