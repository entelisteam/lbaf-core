<?php

declare(strict_types=1);

use EntelisTeam\Lbaf\Core\Rector\MigrationList;
use Rector\Config\RectorConfig;

//вся цепочка миграций целиком — так же, как её собирает RectorMigrationManager в rector.php
$config = RectorConfig::configure();

foreach (MigrationList::all() as $migration) {
    $config = $migration::apply($config);
}

return $config;
