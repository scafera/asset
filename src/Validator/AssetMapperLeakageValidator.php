<?php

declare(strict_types=1);

namespace Scafera\Asset\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class AssetMapperLeakageValidator implements ValidatorInterface
{
    public function getId(): string
    {
        return 'asset.assetmapper-leakage';
    }

    public function getName(): string
    {
        return 'No AssetMapper imports in userland';
    }

    public function validate(string $projectDir): array
    {
        $srcDir = $projectDir . '/src';
        if (!is_dir($srcDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($srcDir) as $file) {
            $contents = file_get_contents($file);
            $relative = 'src/' . str_replace($srcDir . '/', '', $file);

            if (preg_match('/^use\s+Symfony\\\\Component\\\\AssetMapper\\\\[{A-Z]/m', $contents)) {
                $violations[] = $relative . ': imports AssetMapper types directly — use asset() in Twig templates instead';
            }
        }

        return $violations;
    }
}
