<?php

declare(strict_types=1);

namespace EntelisTeam\Lbaf\Core\Rector\Migration;

use EntelisTeam\Lbaf\Core\Rector\Migration\Rules\MoveInjectAttributesToParametersRule;
use Rector\Configuration\RectorConfigBuilder;

/**
 * Миграция: переносит атрибуты Inject* (InjectGet/InjectPost/InjectHeader/InjectEnv)
 * с уровня метода на уровень параметров.
 *
 * До:
 *   #[InjectGet('bar')] function foo(string $bar) {}
 * После:
 *   function foo(#[InjectGet()] string $bar) {}
 */
final class Migration_20260106_2152_MoveInjectAttributesToParameters
{
    public static function apply(RectorConfigBuilder $config): RectorConfigBuilder
    {
        return $config->withRules([
            MoveInjectAttributesToParametersRule::class,
        ]);
    }
}
