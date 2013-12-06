<?php

namespace Kryn\CmsBundle\Tests\Service\Caching;

use Kryn\CmsBundle\Tests\KernelAwareTestCase;

class BasicTest extends KernelAwareTestCase
{
    public function testGeneral()
    {
        $cache = $this->getKrynCore()->getSystemConfig()->getCache(true);
        $class = $cache->getClass();

        $this->assertEquals('Kryn\CmsBundle\Cache\Files', $class);

        //invalidation check
        $this->assertTrue($this->getKrynCore()->getCache()->set('core/test/2', 'Test Object number 2'));

        $this->assertTrue($this->getKrynCore()->invalidateCache('core/test'));
        usleep(1000*50); //50ms
        $this->assertNull($this->getKrynCore()->getCache()->get('core/test/2'));

        //without invalidation
        $this->assertTrue($this->getKrynCore()->getCache()->set('core/test/2', 'Test Object number 2'));
        $this->assertEquals('Test Object number 2', $this->getKrynCore()->getCache()->get('core/test/2'));
    }

}
