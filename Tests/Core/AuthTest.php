<?php

namespace Kryn\CmsBundle\Tests\Core;

use Kryn\CmsBundle\Model\Acl;
use Kryn\CmsBundle\Tests\AuthTestCase;
use Propel\Runtime\Map\TableMap;

class AuthTest extends AuthTestCase
{
    protected $acls;


    public function tearDown()
    {
        if ($this->acls) {
            foreach ($this->acls as $acl) {
                $acl->delete();
            }
        }

        parent::tearDown();
    }

    public function testNewUserLogin()
    {
        $response = $this->restCall('/kryn/admin/login', 'POST', ['username' => 'test', 'password' => 'test']);

        $this->assertInternalType('array', $response['data']);
        $this->assertEquals($this->userPk['id'], $response['data']['userId']);
        $this->assertEquals(false, $response['data']['access']);
        $this->assertEquals(32, strlen($response['data']['token']));
    }

    public function testNewValidUserLogin()
    {

        $this->acls[] = $acl = new Acl();
        $acl->toArray();
        $acl->fromArray(
            [
                'object' => 'kryncms/entryPoint',
                'targetType' => \Kryn\CmsBundle\ACL::GROUP,
                'targetId' => $this->testGroupPk['id'],
                'sub' => true,
                'mode' => 0,
                'access' => true,
                'constraintType' => 1,
                'constraintCode' => $this->getObjects()->getObjectUrlId('kryncms/entryPoint', '/admin'),
            ],
            TableMap::TYPE_STUDLYPHPNAME
        );
        $acl->save();

        $response = $this->restCall('/kryn/admin/login', 'POST', ['username' => 'test', 'password' => 'test']);

        $this->assertInternalType('array', $response['data']);
        $this->assertEquals($this->userPk['id'], $response['data']['userId']);
        $this->assertEquals(true, $response['data']['access']);
        $this->assertEquals(32, strlen($response['data']['token']));
    }

}