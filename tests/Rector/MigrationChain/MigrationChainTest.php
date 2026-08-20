<?php

declare(strict_types=1);

namespace Tests\Rector\MigrationChain;

use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

/**
 * Проверяет, что миграции отрабатывают сцепленно за один запуск:
 * атрибут с уровня метода переезжает в параметр, namespace обновляется,
 * и только после этого InjectGet с PackerType превращается в InjectGetPacked.
 */
final class MigrationChainTest extends AbstractRectorTestCase
{
    #[DataProvider('provideData')]
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    public static function provideData(): Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
