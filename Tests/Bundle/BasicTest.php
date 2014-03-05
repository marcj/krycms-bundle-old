<?php

namespace Kryn\CmsBundle\Tests\Bundle;


use Kryn\CmsBundle\Tests\KernelAwareTestCase;
use Kryn\CmsBundle\Tools;

class BasicTest extends KernelAwareTestCase
{
    public function testGeneral()
    {
        $this->assertTrue($this->getKrynCore()->isActiveBundle('KrynCmsBundle'));
        $this->assertTrue($this->getKrynCore()->isActiveBundle('KrynPublicationBundle'));
        $this->assertTrue($this->getKrynCore()->isActiveBundle('KrynDemoThemeBundle'));

        $this->assertTrue($this->getKrynCore()->isActiveBundle('Kryn\CmsBundle\KrynCmsBundle'));
        $this->assertTrue($this->getKrynCore()->isActiveBundle('Kryn\Publication\KrynPublicationBundle'));
        $this->assertTrue($this->getKrynCore()->isActiveBundle('Kryn\DemoTheme\KrynDemoThemeBundle'));
    }

    public function testResolvePath()
    {
        $path = $this->getKrynCore()->resolvePath('@KrynPublicationBundle/Test', 'Resources/views', true);
        $this->assertEquals('../../../vendor/kryncms/publication-bundle/Kryn/Publication/Resources/views/Test', $path);

        $path = $this->getKrynCore()->resolvePath('@KrynPublicationBundle', '', true);
        $this->assertEquals('../../../vendor/kryncms/publication-bundle/Kryn/Publication', $path);

        $path = $this->getKrynCore()->resolvePath('@KrynPublicationBundle/Resources/views/News/list/default.html.twig', '', true);
        $this->assertEquals('../../../vendor/kryncms/publication-bundle/Kryn/Publication/Resources/views/News/list/default.html.twig', $path);

    }

}
