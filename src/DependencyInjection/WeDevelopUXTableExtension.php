<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use WeDevelop\UXTable\DataProvider\DataProviderInterface;
use WeDevelop\UXTable\DataProvider\DoctrineORMProvider;
use WeDevelop\UXTable\Security\OpenerSigner;
use WeDevelop\UXTable\Twig\Component\SortLink;
use WeDevelop\UXTable\Twig\Component\Table;
use WeDevelop\UXTable\Twig\OpenerExtension;
use WeDevelop\UXTable\Twig\SortExtension;

final class WeDevelopUXTableExtension extends Extension
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

        $container->registerForAutoconfiguration(DataProviderInterface::class)
            ->addTag(DataProviderInterface::class);

        $container->register(DoctrineORMProvider::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;
    }
}
