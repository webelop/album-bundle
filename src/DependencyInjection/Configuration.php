<?php

namespace Webelop\AlbumBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for AlbumBundle
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('webelop_album');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('album_root')->isRequired()->end()
                ->scalarNode('salt')->isRequired()->end()
                ->scalarNode('cache_path')->defaultValue('%kernel.project_dir%/public/pictures')->end()
                ->booleanNode('execute_resize')->defaultValue(false)->end()
            ->end();

        return $treeBuilder;
    }
}
