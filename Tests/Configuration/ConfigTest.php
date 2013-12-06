<?php

namespace Tests\Core;

use Kryn\CmsBundle\Configuration\Cache;
use Kryn\CmsBundle\Configuration\Client;
use Kryn\CmsBundle\Configuration\Database;
use Kryn\CmsBundle\Configuration\Errors;
use Kryn\CmsBundle\Configuration\FilePermission;
use Kryn\CmsBundle\Configuration\SessionStorage;
use Kryn\CmsBundle\Configuration\SystemConfig;
use Kryn\CmsBundle\Configuration\Connection;
use Kryn\CmsBundle\Tests\KernelAwareTestCase;

class ConfigTest extends KernelAwareTestCase
{
    public function testBasics()
    {
        $this->assertCount(10, $this->getKrynCore()->getBundles());
        $this->assertCount(4, $this->getKrynCore()->getConfigs()->getConfigs());
        $this->assertCount(4, $this->getKrynCore()->getConfigs()->getConfigs());
    }

    public function testConfigs()
    {
        $config = $this->getKrynCore()->getConfigs();

        foreach ($config->getConfigs() as $config) {
            $this->assertInstanceOf('Kryn\CmsBundle\Configuration\Bundle', $config);
        }
    }

    public function testBundleConfigs()
    {
        foreach ($this->getKrynCore()->getBundles() as $bundle => $obj) {
            $bundleConfig = $this->getKrynCore()->getConfig($bundle);
            if ($bundleConfig) {
                $this->assertInstanceOf('Kryn\CmsBundle\Configuration\Bundle', $bundleConfig);
            }
        }

        $bundleConfig = $this->getKrynCore()->getConfig('KrynPublicationBundle');
        $this->assertInstanceOf('Kryn\CmsBundle\Configuration\Bundle', $bundleConfig);

        $this->assertEquals('KrynPublicationBundle', $bundleConfig->getBundleName());
        $this->assertEquals('krynpublication', $bundleConfig->getName());
    }

    public function testBundle()
    {
        foreach ($this->getKrynCore()->getBundles() as $bundle => $obj) {
            $this->assertInstanceOf('Symfony\Component\HttpKernel\Bundle\BundleInterface', $obj);
        }

        $bundleConfig = $this->getKrynCore()->getConfig('KrynPublicationBundle');
        $this->assertInstanceOf('Kryn\CmsBundle\Configuration\Bundle', $bundleConfig);

        $this->assertEquals('KrynPublicationBundle', $bundleConfig->getBundleName());
        $this->assertEquals('krynpublication', $bundleConfig->getName());
        $this->assertEquals('Kryn\Publication\KrynPublicationBundle', get_class($bundleConfig->getBundleClass()));

        $this->assertEquals('Kryn\Publication', $bundleConfig->getNamespace());
    }
}