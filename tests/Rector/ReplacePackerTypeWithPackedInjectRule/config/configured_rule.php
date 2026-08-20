<?php

declare(strict_types=1);

use EntelisTeam\Lbaf\Core\Rector\Migration\Rules\ReplacePackerTypeWithPackedInjectRule;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([
        ReplacePackerTypeWithPackedInjectRule::class,
    ])
    ->withImportNames(importNames: true, importDocBlockNames: true, importShortClasses: false, removeUnusedImports: true);
