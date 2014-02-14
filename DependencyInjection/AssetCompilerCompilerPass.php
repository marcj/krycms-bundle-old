<?php

namespace Kryn\CmsBundle\DependencyInjection;

use \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AssetCompilerCompilerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition(
            'kryn_cms.asset_handler.container'
        );

        $compilerServices = $container->findTaggedServiceIds(
            'kryn_cms.asset_handler.compiler'
        );

        foreach ($compilerServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                if (isset($attributes['extension'])) {
                    foreach ((array)$attributes['extension'] as $contentType) {
                        $definition->addMethodCall(
                            'registerCompileHandlerByExtension',
                            array($contentType, $id)
                        );
                    }
                }
            }
        }

        $loaderServices = $container->findTaggedServiceIds(
            'kryn_cms.asset_handler.loader'
        );

        foreach ($loaderServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                if (isset($attributes['contentType'])) {
                    foreach ((array)$attributes['contentType'] as $contentType) {
                        $definition->addMethodCall(
                            'registerLoaderHandlerByContentType',
                            array($contentType, $id)
                        );
                    }
                }
                if (isset($attributes['extension'])) {
                    foreach ((array)$attributes['extension'] as $extension) {
                        $definition->addMethodCall(
                            'registerLoaderHandlerByExtension',
                            array($extension, $id)
                        );
                    }
                }
            }
        }
    }
}