<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ux_table');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('use_default_orm_provider')
                    ->info('Disable this if you do not have Doctrine ORM installed (eg, using Mongo).')
                    ->defaultTrue()
                ->end()
                ->arrayNode('opener')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('secret')->defaultValue('ThisIsNotSoSecret')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
