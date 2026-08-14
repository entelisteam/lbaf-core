<?php

namespace EntelisTeam\Lbaf\Core\View;

abstract class AbstractView
{
    abstract public function renderTemplate(): void;

    abstract public function render(): void;
}
