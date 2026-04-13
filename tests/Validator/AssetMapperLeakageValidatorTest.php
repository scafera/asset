<?php

declare(strict_types=1);

namespace Scafera\Asset\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Asset\Validator\AssetMapperLeakageValidator;

class AssetMapperLeakageValidatorTest extends TestCase
{
    private AssetMapperLeakageValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new AssetMapperLeakageValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_asset_test_' . uniqid();
        mkdir($this->tmpDir . '/src', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenNoAssetMapperImports(): void
    {
        file_put_contents($this->tmpDir . '/src/HomeController.php', <<<'PHP'
        <?php
        namespace App\Controller;
        use Scafera\Kernel\Contract\ViewInterface;
        class HomeController {
            public function __construct(private readonly ViewInterface $view) {}
        }
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenAssetMapperImported(): void
    {
        file_put_contents($this->tmpDir . '/src/BadService.php', <<<'PHP'
        <?php
        namespace App\Service;
        use Symfony\Component\AssetMapper\AssetMapper;
        class BadService {
            public function __construct(private readonly AssetMapper $mapper) {}
        }
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('BadService.php', $violations[0]);
        $this->assertStringContainsString('asset()', $violations[0]);
    }

    public function testFailsWhenAssetMapperSubclassImported(): void
    {
        file_put_contents($this->tmpDir . '/src/BadController.php', <<<'PHP'
        <?php
        namespace App\Controller;
        use Symfony\Component\AssetMapper\MappedAsset;
        class BadController {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('BadController.php', $violations[0]);
    }

    public function testFailsWhenGroupedImportUsed(): void
    {
        file_put_contents($this->tmpDir . '/src/GroupedImport.php', <<<'PHP'
        <?php
        namespace App\Service;
        use Symfony\Component\AssetMapper\{AssetMapper, MappedAsset};
        class GroupedImport {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('GroupedImport.php', $violations[0]);
    }

    public function testPassesWhenNoSrcDir(): void
    {
        $emptyDir = sys_get_temp_dir() . '/scafera_empty_' . uniqid();
        mkdir($emptyDir);

        $this->assertSame([], $this->validator->validate($emptyDir));

        rmdir($emptyDir);
    }

    public function testReportsMultipleViolations(): void
    {
        file_put_contents($this->tmpDir . '/src/Bad1.php', <<<'PHP'
        <?php
        use Symfony\Component\AssetMapper\AssetMapper;
        class Bad1 {}
        PHP);

        mkdir($this->tmpDir . '/src/Sub', 0777, true);
        file_put_contents($this->tmpDir . '/src/Sub/Bad2.php', <<<'PHP'
        <?php
        use Symfony\Component\AssetMapper\MappedAsset;
        class Bad2 {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(2, $violations);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }
}
