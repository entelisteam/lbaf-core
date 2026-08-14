<?php

namespace EntelisTeam\Lbaf\Core\Response;

use EntelisTeam\Lbaf\ConsoleTable\ConsoleTable;

class CliResponse extends AbstractResponse
{

    public function __construct(protected mixed $content)
    {
    }

    /**
     * @inheritDoc
     */
    public function pack(): string
    {

        $data = $this->content;

        if (is_string($data)) {
            return $data . PHP_EOL;
        } elseif (is_numeric($data) || is_bool($data)) {
            return (string)$data . PHP_EOL;
        };

        if (is_object($data)) {
            $data = (array)$data;
        }
        //пытаемся понять что внутри
        $item = array_values($data)[0];
        if (is_object($item) || is_array($item)) {
            return ConsoleTable::fromRows($data);
        } else {
            return ConsoleTable::fromMap($data);
        }

    }

    /**
     * @inheritDoc
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }
}