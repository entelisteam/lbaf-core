<?php

declare(strict_types=1);

use EntelisTeam\Lbaf\Core\Rector\Migration\Rules\MoveInjectAttributesToParametersRule;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([
        MoveInjectAttributesToParametersRule::class,
    ]);
