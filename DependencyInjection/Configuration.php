<?php

namespace Billhance\RemoteAssetsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('billhance_remote_assets');

        $rootNode
            ->children()
                ->scalarNode('target')
                    ->defaultValue('web')
                ->end()
                ->arrayNode('assets')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('origin')->end()
                            ->scalarNode('destination')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
