<?php

namespace EntelisTeam\Lbaf\Core\Packer;

/**
 * @todo подумать над namespace - это пересекается с Packer, не уверен что он вообще нужен, возможно везде где он есть стоит явно передавать PackerInterface
 */
enum PackerType: string
{
    case Json = Json::class;
}
