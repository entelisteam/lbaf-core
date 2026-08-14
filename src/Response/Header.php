<?php

namespace EntelisTeam\Lbaf\Core\Response;

class Header
{
    public string $title;
    public string $value;

    /**
     * @param string $title
     * @param string $value
     */
    public function __construct(string $title, string $value)
    {
        $this->title = $title;
        $this->value = $value;
    }

    function send(): void
    {
        header($this->title . ': ' . $this->value);
    }
}