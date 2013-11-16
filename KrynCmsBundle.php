<?php

namespace Kryn\CmsBundle;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\ClassLoader\UniversalClassLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class KrynCmsBundle extends Bundle
{
    public function boot()
    {
        parent::boot();
        $loader = new UniversalClassLoader();
        $loader->registerNamespaceFallback($this->container->get('kernel')->getCacheDir().'/propel-classes/');
        $loader->register();
    }
}
