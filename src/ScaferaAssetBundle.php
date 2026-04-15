<?php

declare(strict_types=1);

namespace Scafera\Asset;

use Scafera\Kernel\InstalledPackages;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

final class ScaferaAssetBundle extends AbstractBundle
{
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $projectDir = $builder->getParameter('kernel.project_dir');
        $architecture = InstalledPackages::resolveArchitecture($projectDir);
        $assetsDir = $architecture?->getAssetsDir();

        if ($assetsDir === null) {
            return;
        }

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [$assetsDir . '/'],
            ],
        ]);

        if (isset($builder->getExtensions()['symfonycasts_tailwind'])) {
            $builder->prependExtensionConfig('symfonycasts_tailwind', [
                'input_css' => ['%kernel.project_dir%/' . $assetsDir . '/styles/app.css'],
            ]);
        }
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->services()
            ->set(Validator\AssetMapperLeakageValidator::class)
                ->tag('scafera.validator');
    }
}
