<?php

namespace Kryn\CmsBundle;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Kryn\CmsBundle\DependencyInjection\ContentTypesCompilerPass;
use Symfony\Component\ClassLoader\UniversalClassLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class KrynCmsBundle extends Bundle
{
    /**
     * @var UniversalClassLoader
     */
    protected $additionalLoader;

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new ContentTypesCompilerPass());
    }

    public function boot()
    {
        parent::boot();
        $this->additionalLoader = new UniversalClassLoader();
        $this->additionalLoader->registerNamespaceFallback($this->container->get('kernel')->getCacheDir().'/propel-classes/');
        $this->additionalLoader->register();
    }

    /**
     * Shutdowns the Bundle.
     */
    public function shutdown()
    {
        unset($this->additionalLoader);
    }
}
