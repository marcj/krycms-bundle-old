<?php

namespace Kryn\CmsBundle\DependencyInjection;

use \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FieldTypesCompilerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('kryn_cms.field.types')) {
            return;
        }

        $definition = $container->getDefinition(
            'kryn_cms.field.types'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'kryn_cms.field.type'
        );

        foreach ($taggedServices as $id => $tagAttributes) {
            $tagDef = $container->getDefinition($id);
            $tagDef->setScope('prototype');

            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall(
                    'addType',
                    array($attributes['alias'], $id)
                );
            }
        }
    }
}