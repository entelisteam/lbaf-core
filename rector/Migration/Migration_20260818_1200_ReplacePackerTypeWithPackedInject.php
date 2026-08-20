<?php

declare(strict_types=1);

namespace EntelisTeam\Lbaf\Core\Rector\Migration;

use EntelisTeam\Lbaf\Core\Rector\Migration\Rules\ReplacePackerTypeWithPackedInjectRule;
use Rector\Configuration\RectorConfigBuilder;

/**
 * Миграция: InjectGet/InjectPost больше не принимают PackerType,
 * вместо них используются InjectGetPacked/InjectPostPacked.
 *
 * До:
 *   function foo(#[InjectGet(packerType: PackerType::Json)] Request $request) {}
 * После:
 *   function foo(#[InjectGetPacked] Request $request) {}
 */
final class Migration_20260818_1200_ReplacePackerTypeWithPackedInject
{
    public static function apply(RectorConfigBuilder $config): RectorConfigBuilder
    {
        return $config
            ->withRules([
                ReplacePackerTypeWithPackedInjectRule::class,
            ])

            //новый атрибут подставляется как FQN, импортируем его через use и убираем ставший ненужным PackerType
            ->withImportNames(importNames: true, importDocBlockNames: true, importShortClasses: false, removeUnusedImports: true);
    }
}
