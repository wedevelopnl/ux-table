<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use WeDevelop\UXTable\Security\OpenerSigner;
use WeDevelop\UXTable\Twig\OpenerExtension;
use WeDevelop\UXTable\Twig\SortExtension;

final class UXTableExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        if (!isset($container->getParameter('kernel.bundles')['UXTableBundle'])) {
            throw new LogicException('The TwigBundle is not registered in your application. Try running "composer require symfony/twig-bundle".');
        }

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->register(OpenerSigner::class, OpenerSigner::class)
            ->setArguments([
                $config['opener']['secret']
            ])
        ;

        $container->register(SortExtension::class, SortExtension::class)
            ->addTag('twig.extension')
            ->setAutowired(true)
        ;

        $container->register(OpenerExtension::class, OpenerExtension::class)
            ->addTag('twig.extension')
            ->setAutowired(true)
        ;
    }
}
