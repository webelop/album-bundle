<?php

namespace Webelop\AlbumBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for AlbumBundle
 *
 * TODO: Add configurable logout route
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
            ->scalarNode('album_root')->isRequired()
                ->info('Path to root folder to expose in the photo manager')
                ->end()
            ->scalarNode('salt')->isRequired()
                ->info('Salt used to generate secure hashes for image urls')
                ->end()
            ->scalarNode('cache_path')
                ->defaultValue('%kernel.project_dir%/public/pictures')
                ->info('This path is internal and points to a web available folder'.
                    'where preview or links to previews are saved')
                ->end()
            ->booleanNode('execute_resize')
                ->defaultValue(false)
                ->info('To avoid using all resources on low-powered devices,' .
                    'resize is disabled and should be done on the client')
                ->end()
            ->booleanNode('use_binary_file_response')
                ->defaultValue(true)
                ->info('In functional tests BinaryFileResponse is not usable. In all other cases it is best.')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
