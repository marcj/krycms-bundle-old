<?php

namespace Kryn\CmsBundle\Tests;

use Kryn\CmsBundle\Model\GroupQuery;
use Kryn\CmsBundle\Model\UserQuery;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthTestCase extends KernelAwareTestCase
{
    protected $testGroupPk;
    protected $userPk;

    public function setUp()
    {
        parent::setUp();

        UserQuery::create()->filterByUsername('test')->delete();
        GroupQuery::create()->filterByName('TestGroup')->delete();

        if ($this->testGroupPk) {
            return;
        }

        $this->testGroupPk = $this->getObjects()->add(
            'kryncms/group',
            [
                'name' => 'TestGroup'
            ]
        );

        $this->userPk = $this->getObjects()->add(
            'kryncms/user',
            [
                'username' => 'test',
                'password' => 'test',
                'groupMembership' => [$this->testGroupPk['id']]
            ]
        );
    }

    public function tearDown()
    {
        if (!$this->testGroupPk) {
            return;
        }
        $this->getObjects()->remove('kryncms/group', $this->testGroupPk);
        $this->getObjects()->remove('kryncms/user', $this->userPk);

        parent::tearDown();
    }
}