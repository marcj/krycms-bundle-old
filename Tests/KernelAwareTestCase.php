<?php

namespace Kryn\CmsBundle\Tests;

use Kryn\CmsBundle\ContainerHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

class KernelAwareTestCase extends WebTestCase
{
    use ContainerHelperTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->container = static::$kernel->getContainer();
    }
}