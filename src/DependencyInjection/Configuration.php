<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('ux_table');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('opener')
                    ->children()
                        ->scalarNode('secret')->defaultValue('ThisIsNotSoSecret')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
