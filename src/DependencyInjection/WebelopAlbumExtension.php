<?php

namespace Webelop\AlbumBundle\DependencyInjection;

use InvalidArgumentException;
use Webelop\AlbumBundle\Service\FolderManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Webelop\AlbumBundle\Service\PictureManager;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class WebelopAlbumExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $definition = $container->getDefinition('webelop_album.folder_manager');
        $definition->replaceArgument(0, $config);

        $definition = $container->getDefinition('webelop_album.picture_manager');
        $definition->replaceArgument(0, $config);

        $definition = $container->getDefinition('webelop_album.controller.media_controller');
        $definition->replaceArgument(0, $config);
    }

    public function getAlias()
    {
        return 'webelop_album';
    }

}
