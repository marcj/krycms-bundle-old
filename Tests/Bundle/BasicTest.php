<?php

namespace Kryn\CmsBundle\Tests\Bundle;


use Kryn\CmsBundle\Tests\KernelAwareTestCase;
use Kryn\CmsBundle\Tools;

class BasicTest extends KernelAwareTestCase
{
    public function xtestTools()
    {
        $path = "/Users/marc/bude/symfony-24/src/Kryn/CmsBundle";
        $current = "/Users/marc/bude/symfony-24/src/Kryn/CmsBundle/Tests/Integration/skeletion";

        $relative = Tools::resolveRelativePath($path, $current);

        var_dump($path);
        var_dump($current);
        var_dump($relative);
    }

    public function testGeneral()
    {
        $this->assertTrue($this->getKrynCore()->isActiveBundle('KrynCmsBundle'));
        $this->assertTrue($this->getKrynCore()->isActiveBundle('KrynPublicationBundle'));
        $this->assertTrue($this->getKrynCore()->isActiveBundle('KrynDemoThemeBundle'));

        $this->assertTrue($this->getKrynCore()->isActiveBundle('Kryn\CmsBundle\KrynCmsBundle'));
        $this->assertTrue($this->getKrynCore()->isActiveBundle('Kryn\Publication\KrynPublicationBundle'));
        $this->assertTrue($this->getKrynCore()->isActiveBundle('Kryn\DemoTheme\KrynDemoThemeBundle'));
    }

}
