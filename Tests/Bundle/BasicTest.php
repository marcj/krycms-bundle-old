<?php

namespace Kryn\CmsBundle\Tests\Bundle;


use Kryn\CmsBundle\Tests\KernelAwareTestCase;
use Kryn\CmsBundle\Tools;

class BasicTest extends KernelAwareTestCase
{
    public function testRelativePath()
    {
        $relative = Tools::getRelativePath('/anotherroot/web/file', '/root/web/other/dir');
        $this->assertEquals('../../../../anotherroot/web/file', $relative);

        $relative = Tools::getRelativePath('/root/web/file', '/root/web/other/dir');
        $this->assertEquals('../../file', $relative);

        $relative = Tools::getRelativePath('/root/web/dir/file/', '/root/web/dir');
        $this->assertEquals('file', $relative);

        $relative = Tools::getRelativePath('/root/web/other/file/', '/root/web/dir');
        $this->assertEquals('../other/file', $relative);
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
