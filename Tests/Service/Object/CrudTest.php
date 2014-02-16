<?php

namespace Tests\Object;

use Kryn\CmsBundle\Model\DomainQuery;
use Kryn\CmsBundle\Model\NodeQuery;
use Kryn\CmsBundle\Tests\KernelAwareTestCase;

class CreateTest extends KernelAwareTestCase
{
    protected $nodePk = null;

    public function testObject()
    {
        $this->getObjects()->clear('Test\\Test');

        //check empty
        $count = $this->getObjects()->getCount('Test\\Test');
        $this->assertEquals(0, $count);

        //new object
        $values = array('name' => 'Hallo "\'Peter, ✔');
        $pk = $this->getObjects()->add('Test\\Test', $values);

        //check if inserted correctly
        $this->assertArrayHasKey('id', $pk);
        $this->assertGreaterThan(0, $pk['id']);

        //get through single value pk and check result
        $item = $this->getObjects()->get('Test\\Test', $pk['id']);
        $this->assertGreaterThan(0, $item['id']);
        $this->assertEquals($values['name'], $item['name']);

        //get through array pk and check result
        $item = $this->getObjects()->get('Test\\Test', $pk);
        $this->assertGreaterThan(0, $item['id']);
        $this->assertEquals($values['name'], $item['name']);

        //check count
        $count = $this->getObjects()->getCount('Test\\Test');
        $this->assertGreaterThan(0, $count);

        //remove
        $this->getObjects()->remove('Test\\Test', $pk);

        //check empty
        $count = $this->getObjects()->getCount('Test\\Test');
        $this->assertEquals(0, $count);
    }

    public function testAdd()
    {
        $date = ('+'. rand(2, 30) . ' days +' . rand(2, 24) . ' hours');
        $values = array(
            'title' => 'News item',
            'intro' => 'Lorem ipsum',
            'newsDate' => strtotime($date)
        );
        $pk = $this->getObjects()->add('KrynPublicationBundle:News', $values);

        $item = $this->getObjects()->get('KrynPublicationBundle:News', $pk);

        $this->assertEquals($values['title'], $item['title']);
        $this->assertEquals($values['intro'], $item['intro']);
        $this->assertEquals($values['newsDate'], $item['newsDate']);

        $this->assertTrue($this->getObjects()->remove('KrynPublicationBundle:News', $pk));

        $this->assertNull($this->getObjects()->get('KrynPublicationBundle:News', $pk));
    }

    protected function getNewNodeItem()
    {
        $domain = DomainQuery::create()->findOne();

        $values = array(
            'title' => 'Test page',
            'domain' => $domain->getId(),
            'type' => 0
        );

        $pk = $this->getObjects()->add(
            'kryncms/node',
            $values,
            $domain->getId(),
            'first',
            'kryncms/domain'
        );
        return $this->nodePk = $pk;
    }

    public function testPatch()
    {
        $nodePk = $this->getNewNodeItem();

        $ori = clone NodeQuery::create()->findOneById($nodePk['id']);
        $this->getObjects()->patch('kryncms/node', $nodePk, [
           'title' => 'Test page changed'
        ]);

        $node = NodeQuery::create()->findOneById($nodePk['id']);
        $this->assertEquals('Test page changed', $node->getTitle());
        $this->assertEquals($ori->getDomainId(), $node->getDomainId());
    }

    public function tearDown()
    {
        if ($this->nodePk) {
            NodeQuery::create()->filterById($this->nodePk['id'])->delete();
        }
    }
}
