<?php

namespace Vallen\StoredProcedureFactory\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('vallen_stored_procedure');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('hostname')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('Database hostname')
                ->end()
                ->scalarNode('username')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('Database username')
                ->end()
                ->scalarNode('password')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('Database password')
                ->end()
            ->end();

        return $treeBuilder;
    }
}