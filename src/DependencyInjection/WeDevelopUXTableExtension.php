<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\DependencyInjection;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use WeDevelop\UXTable\DataProvider\DataProviderInterface;
use WeDevelop\UXTable\DataProvider\DoctrineORMProvider;
use WeDevelop\UXTable\Security\OpenerSigner;
use WeDevelop\UXTable\Twig\Component\Filter;
use WeDevelop\UXTable\Twig\Component\PageSize;
use WeDevelop\UXTable\Twig\Component\SortLink;
use WeDevelop\UXTable\Twig\Component\Table;
use WeDevelop\UXTable\Twig\OpenerExtension;
use WeDevelop\UXTable\Twig\SortExtension;

final class WeDevelopUXTableExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->register(OpenerSigner::class, OpenerSigner::class)
            ->setArguments([
                $config['opener']['secret']
            ])
        ;

        $container->register(OpenerExtension::class, OpenerExtension::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->register(SortExtension::class, SortExtension::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->register(Table::class, Table::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->register(SortLink::class, SortLink::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->register(Filter::class, Filter::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->register(PageSize::class, PageSize::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->registerForAutoconfiguration(DataProviderInterface::class)
            ->addTag(DataProviderInterface::class);

        if ($config['use_default_orm_provider']) {
            $container->register(DoctrineORMProvider::class)
                ->setAutowired(true)
                ->setAutoconfigured(true);
        }
    }

    public function prepend(ContainerBuilder $container)
    {
        if ($this->isAssetMapperAvailable($container)) {
            $container->prependExtensionConfig('framework', [
                'asset_mapper' => [
                    'paths' => [
                        __DIR__.'/../../assets/src' => 'wedevelopnl/ux-table',
                    ],
                ],
            ]);
        }
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }
}
