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

    protected $allCookies;

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->container = static::$kernel->getContainer();

        \Kryn\CmsBundle\Configuration\Model::$serialisationKrynCore = $this->getKrynCore();
    }

    public function login()
    {
        //login as admin
        $loggedIn = $this->restCall('/kryn/admin/logged-in');

        if (!$loggedIn || !$loggedIn['data']) {
            $this->restCall('/kryn/admin/login', 'POST', ['username' => 'admin', 'password' => 'admin']);
        }
    }

    protected function getRoot()
    {
        return realpath($this->getKernel()->getRootDir() . '/..') . '/';
    }

    public function restCall($path = '/', $method = 'GET', $postData = null, $failOnError = true)
    {
        $content = $this->call($path, $method, $postData);

        $data = json_decode($content, true);

        if ($failOnError && (!is_array($data) || @$data['error'])) {
            $this->fail(
                "path $path, method: $method:\n" .
                var_export($content, true)
            );
        }

        return !json_last_error() ? $data : $content;
    }

    public function call($path = '/', $method = 'GET', $parameters = [])
    {
        $client = static::createClient();

        $server = [];

        if (!$parameters) {
            $parameters = [];
        }

        if ($this->allCookies) {
            foreach ($this->allCookies as $cookie) {
                $client->getCookieJar()->set($cookie);
            }
        }

        $client->request($method, $path, $parameters, $files = array(), $server);

        $this->allCookies = $client->getCookieJar()->all();

        return $client->getInternalResponse()->getContent();
    }

    public function resetCookies()
    {
        $this->allCookies = null;
    }
}