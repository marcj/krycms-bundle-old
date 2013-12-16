<?php

namespace Kryn\CmsBundle\DependencyInjection;

use \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ModelBuilderCompilerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('kryn_cms.model.builder')) {
            return;
        }

        $definition = $container->getDefinition(
            'kryn_cms.model.builder'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'kryn_cms.model.builder'
        );

        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall(
                    'addBuilder',
                    array($attributes['alias'], new Reference($id))
                );
            }
        }
    }
}