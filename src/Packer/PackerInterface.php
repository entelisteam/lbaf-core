<?php

namespace EntelisTeam\Lbaf\Core\Packer;

use EntelisTeam\Lbaf\Core\Response\Header;

/**
 * Описывает класс-упаковщик данных.
 * DISCLAIMER: Для внешних данных не используйте опасную распаковку типа msg_pack или serialize
 */
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
     * Возвращает заголовки по умолчанию для этого Packer
     * @return Header[] массив заголовок
     */
    public function getHeaders(): array;

}
