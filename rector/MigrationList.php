<?php

declare(strict_types=1);

namespace EntelisTeam\Lbaf\Core\Rector;

use EntelisTeam\Lbaf\Core\Rector\Migration\Migration_20260106_2152_MoveInjectAttributesToParameters;
use EntelisTeam\Lbaf\Core\Rector\Migration\Migration_20260814_1200_MoveLbafNamespaceToLbafCore;
use EntelisTeam\Lbaf\Core\Rector\Migration\Migration_20260818_1200_ReplacePackerTypeWithPackedInject;
use EntelisTeam\Lbaf\Rector\RectorMigrationListInterface;

/**
 * Реестр Rector-миграций lbaf.
 *
 * Регистрируется через composer.json `extra.lbaf-rector-migrations`;
 * автоматически подхватывается Manager-ом.
 */
final class MigrationList implements RectorMigrationListInterface
{
    /**
     * @return list<class-string>
     */
    public static function all(): array
    {
        return [
            Migration_20260106_2152_MoveInjectAttributesToParameters::class,
            Migration_20260814_1200_MoveLbafNamespaceToLbafCore::class,
            Migration_20260818_1200_ReplacePackerTypeWithPackedInject::class,
        ];
    }
}
