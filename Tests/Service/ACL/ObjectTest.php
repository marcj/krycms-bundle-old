<?php

namespace Kryn\CmsBundle\Tests\Service\ACL;

use Kryn\CmsBundle\Configuration\Condition;
use Kryn\CmsBundle\Model\Acl;
use Kryn\CmsBundle\Model\DomainQuery;
use Kryn\CmsBundle\Model\Group;
use Kryn\CmsBundle\Model\Node;
use Kryn\CmsBundle\Model\NodeQuery;
use Kryn\CmsBundle\Model\User;
use Kryn\CmsBundle\Tests\AuthTestCase;
use Kryn\CmsBundle\Tests\KernelAwareTestCase;
use Test\Model\Item;
use Test\Model\ItemCategory;
use Test\Model\ItemCategoryQuery;
use Test\Model\ItemQuery;
use Test\Model\TestQuery;

class ObjectTest extends AuthTestCase
{
    public function testConditionToSql()
    {

        $condition = new Condition();

        $condition2 = new Condition();
        $condition2->addAnd([
            'title', '=', 'TestNode tree'
        ]);

        $condition->addAnd($condition2);
        $condition->addOr([
            '1', '=', '1'
        ]);

        $params = [];
        $sql = $condition->toSql($params, 'kryncms/node');

        $expectedArray = [
            [
                ['title', '=', 'TestNode tree']
            ],
            'OR',
            [
                '1', '=', '1'
            ]
        ];
        $this->assertEquals($expectedArray, $condition->toArray());
        $this->assertEquals([':p1' => 'TestNode tree'], $params);
        $this->assertEquals(' kryn_system_node.title = :p1  OR 1= 1', $sql);
    }

    public function testNestedSubPermission()
    {
        $this->getACL()->setCaching(false);
        $this->getACL()->removeObjectRules('kryncms/node');

        $this->getKrynCore()->getClient()->login('test', 'test');
        $user = $this->getKrynCore()->getClient()->getUser();

        $domain = DomainQuery::create()->findOne();
        $root = NodeQuery::create()->findRoot($domain->getId());

        $subNode = new Node();
        $subNode->setTitle('TestNode tree');
        $subNode->insertAsFirstChildOf($root);
        $subNode->save();

        $subNode2 = new Node();
        $subNode2->setTitle('TestNode sub');
        $subNode2->insertAsFirstChildOf($subNode);
        $subNode2->Save();

        //make access for all
        $rule = new Acl();
        $rule->setAccess(true);
        $rule->setObject('kryncms/node');
        $rule->setTargetType(\Kryn\CmsBundle\ACL::USER);
        $rule->setTargetId($user->getId());
        $rule->setMode(\Kryn\CmsBundle\ACL::ALL);
        $rule->setConstraintType(\Kryn\CmsBundle\ACL::CONSTRAINT_ALL);
        $rule->setPrio(2);
        $rule->save();

        //revoke access for all children of `TestNode tree`
        $rule2 = new Acl();
        $rule2->setAccess(false);
        $rule2->setObject('kryncms/node');
        $rule2->setTargetType(\Kryn\CmsBundle\ACL::USER);
        $rule2->setTargetId($user->getId());
        $rule2->setMode(\Kryn\CmsBundle\ACL::ALL);
        $rule2->setConstraintType(\Kryn\CmsBundle\ACL::CONSTRAINT_CONDITION);
        $rule2->setConstraintCode(json_encode([
            'title', '=', 'TestNode tree'
        ]));
        $rule2->setPrio(1);
        $rule2->setSub(true);
        $rule2->save();

        $this->assertFalse($this->getACL()->checkListExact('kryncms/node', $subNode->getId()));
        $this->assertFalse($this->getACL()->checkListExact('kryncms/node', $subNode2->getId()));

        $items = $this->getObjects()->getBranch('kryncms/node', $subNode->getId(), null, 1, null, [
            'permissionCheck' => true
        ]);
        $this->assertNull($items, 'rule2 revokes the access to all elements');
        $item = $this->getObjects()->get('kryncms/node', $subNode2->getId(), [
            'permissionCheck' => true
        ]);
        $this->assertNull($item);



        // Deactivate sub
        $rule2->setSub(false);
        $rule2->save();

        $this->assertFalse($this->getACL()->checkListExact('kryncms/node', $subNode->getId()));
        $this->assertTrue($this->getACL()->checkListExact('kryncms/node', $subNode2->getId()));
        $items = $this->getObjects()->getBranch('kryncms/node', $subNode->getId(), null, 1, null, [
            'permissionCheck' => true
        ]);
        $this->assertEquals('TestNode sub', $items[0]['title'], 'We got TestNode sub');
        $item = $this->getObjects()->get('kryncms/node', $subNode2->getId(), [
            'permissionCheck' => true
        ]);
        $this->assertEquals('TestNode sub', $item['title'], 'We got TestNode sub');



        // Activate access
        $rule2->setAccess(true);
        $rule2->save();
        $this->assertTrue($this->getACL()->checkListExact('kryncms/node', $subNode->getId()));
        $this->assertTrue($this->getACL()->checkListExact('kryncms/node', $subNode2->getId()));

        $items = $this->getObjects()->getBranch('kryncms/node', $subNode->getId(), null, 1, null, [
            'permissionCheck' => true
        ]);
        $this->assertEquals('TestNode sub', $items[0]['title'], 'We got TestNode sub');


        $subNode->delete();
        $subNode2->delete();
        $rule->delete();
        $rule2->delete();
        $this->getACL()->setCaching(true);
    }

    public function xtestSpeed()
    {
        $item = new Item();
        $item->setTitle('Item 1');
        $item->save();

        debugPrint('start');
        $objectItem = $this->getObjects()->get('test/item', ['id' => $item->getId()]);
        debugPrint('---');
        $objectItem = $this->getObjects()->get('test/item', ['id' => $item->getId()]);
        debugPrint('done');

        $item->delete();
    }

    public function testRuleCustom()
    {
        ItemCategoryQuery::create()->deleteAll();
        ItemQuery::create()->deleteAll();
        TestQuery::create()->deleteAll();
        $this->getACL()->setCaching(true);
        $this->getACL()->removeObjectRules('test/item');

        $user = new User();
        $user->setUsername('testuser');
        $user->save();

        $item1 = new Item();
        $item1->setTitle('Item 1');
        $item1->save();

        $item2 = new Item();
        $item2->setTitle('Item test');
        $item2->save();

        $rule = new Acl();
        $rule->setAccess(true);
        $rule->setObject('test/item');
        $rule->setTargetType(\Kryn\CmsBundle\ACL::USER);
        $rule->setTargetId($user->getId());
        $rule->setMode(\Kryn\CmsBundle\ACL::ALL);
        $rule->setConstraintType(\Kryn\CmsBundle\ACL::CONSTRAINT_ALL);
        $rule->setPrio(2);
        $rule->save();

        $rule = new Acl();
        $rule->setAccess(false);
        $rule->setObject('test/item');
        $rule->setTargetType(\Kryn\CmsBundle\ACL::USER);
        $rule->setTargetId($user->getId());
        $rule->setMode(\Kryn\CmsBundle\ACL::ALL);
        $rule->setConstraintType(\Kryn\CmsBundle\ACL::CONSTRAINT_CONDITION);
        $rule->setConstraintCode(json_encode([
            ['title', 'LIKE', '%test']
        ]));
        $rule->setPrio(1);
        $rule->save();

        $access1 = $this->getACL()->checkListExact(
            'test/item',
            $item1->getId(),
            \Kryn\CmsBundle\ACL::USER,
            $user->getId()
        );

        $access2 = $this->getACL()->checkListExact(
            'test/item',
            $item2->getId(),
            \Kryn\CmsBundle\ACL::USER,
            $user->getId()
        );

        $this->assertTrue($access1, 'item1 has access as the second rule doesnt grab and first rule says all access=true');
        $this->assertFalse($access2, 'no access to item2 as we have defined access=false in second rule.');

        $user->delete();

        $this->getACL()->setCaching(true);
        $this->getACL()->removeObjectRules('test/item');
    }

    public function testRulesWithFields()
    {
        ItemCategoryQuery::create()->deleteAll();
        ItemQuery::create()->deleteAll();
        TestQuery::create()->deleteAll();
        $this->getACL()->setCaching(false);
        $this->getACL()->removeObjectRules('test/item');

        $user = new User();
        $user->setUsername('TestUser');
        $user->save();

        $group = new Group();
        $group->setName('ACL Test group');
        $group->addUser($user);
        $group->save();

        $cat1 = new ItemCategory();
        $cat1->setName('Nein');

        $item1 = new Item();
        $item1->setTitle('Item 1');
        $item1->addCategory($cat1);
        $item1->save();

        $cat2 = new ItemCategory();
        $cat2->setName('Hiiiii');

        $item2 = new Item();
        $item2->setTitle('Item 2');
        $item2->addCategory($cat2);
        $item2->save();

        $this->getACL()->removeObjectRules('test/item');
        $fields = array(
            'oneCategory' => array(
                array(
                    'access' => false,
                    'condition' => array(array('id', '>', $cat1->getId()))
                )
            )
        );
        $this->getACL()->setObjectUpdate('test/item', \Kryn\CmsBundle\ACL::USER, $user->getId(), true, $fields);

        $this->assertFalse(
            $this->getACL()->checkUpdate(
                'test/item',
                array('oneCategory' => $cat2->getId()),
                \Kryn\CmsBundle\ACL::USER,
                $user->getId()
            )
        );
        $this->assertTrue(
            $this->getACL()->checkUpdate(
                'test/item',
                array('oneCategory' => $cat1->getId()),
                \Kryn\CmsBundle\ACL::USER,
                $user->getId()
            )
        );

        $this->getACL()->removeObjectRules('test/item');
        $fields = array(
            'oneCategory' => array(
                array(
                    'access' => false,
                    'condition' => array(array('name', '=', 'Nein'))
                )
            )
        );

        $this->getACL()->setObjectUpdate('test/item', \Kryn\CmsBundle\ACL::USER, $user->getId(), true, $fields);

        $this->assertTrue(
            $this->getACL()->checkUpdate(
                'test/item',
                array('oneCategory' => $cat2->getId()),
                \Kryn\CmsBundle\ACL::USER,
                $user->getId()
            )
        );
        $this->assertFalse(
            $this->getACL()->checkUpdate(
                'test/item',
                array('oneCategory' => $cat1->getId()),
                \Kryn\CmsBundle\ACL::USER,
                $user->getId()
            )
        );

        $this->getACL()->removeObjectRules('test/item');

        $fields = array(
            'title' => array(
                array(
                    'access' => false,
                    'condition' => array(array('title', 'LIKE', 'peter %'))
                )
            )
        );
        $this->getACL()->setObjectUpdate('test/item', \Kryn\CmsBundle\ACL::USER, $user->getId(), true, $fields);

        $this->assertTrue(
            $this->getACL()->checkUpdate('test/item', array('title' => 'Heidenau'), \Kryn\CmsBundle\ACL::USER, $user->getId())
        );
        $this->assertTrue(
            $this->getACL()->checkUpdate('test/item', array('title' => 'peter'), \Kryn\CmsBundle\ACL::USER, $user->getId())
        );
        $this->assertFalse(
            $this->getACL()->checkUpdate('test/item', array('title' => 'peter 2'), \Kryn\CmsBundle\ACL::USER, $user->getId())
        );
        $this->assertFalse(
            $this->getACL()->checkUpdate('test/item', array('title' => 'peter asdad'), \Kryn\CmsBundle\ACL::USER, $user->getId())
        );

        $this->getACL()->removeObjectRules('test/item');

        $fields = array('title' => array(array('access' => false, 'condition' => array(array('title', '=', 'peter')))));
        $this->getACL()->setObjectUpdate('test/item', \Kryn\CmsBundle\ACL::USER, $user->getId(), true, $fields);

        $this->assertTrue(
            $this->getACL()->checkUpdate('test/item', array('title' => 'Heidenau'), \Kryn\CmsBundle\ACL::USER, $user->getId())
        );
        $this->assertFalse(
            $this->getACL()->checkUpdate('test/item', array('title' => 'peter'), \Kryn\CmsBundle\ACL::USER, $user->getId())
        );
        $this->assertTrue(
            $this->getACL()->checkUpdate('test/item', array('title' => 'peter2'), \Kryn\CmsBundle\ACL::USER, $user->getId())
        );

        $this->getACL()->setCaching(true);
        $this->getACL()->removeObjectRules('test/item');
    }

    public function texxstObjectGeneral()
    {
        ItemQuery::create()->deleteAll();
        TestQuery::create()->deleteAll();
        $this->getACL()->removeObjectRules('test/item');
        $this->getACL()->setCaching(false);

        $user = new User();
        $user->setUsername('TestUser');
        $user->save();

        $group = new Group();
        $group->setName('ACL Test group');
        $group->addGroupMembershipUser($user);
        $group->save();

        $item1 = new Item();
        $item1->setTitle('Item 1');
        $item1->save();

        $item2 = new Item();
        $item2->setTitle('Item 2');
        $item2->save();

        $test1 = new Test();
        $test1->setName('Test 1');
        $test1->save();

        $this->assertFalse(
            $this->getACL()->checkList('test/item', $item1->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId()),
            'we have no rules, so everyone except admin user and admin group has no access.'
        );

        $this->assertTrue(
            $this->getACL()->checkList('test/item', $item1->getId(), \Kryn\CmsBundle\ACL::GROUP, 1),
            'we have no rules, so only group admin has access.'
        );

        $this->assertTrue(
            $this->getACL()->checkList('test/item', $item1->getId(), \Kryn\CmsBundle\ACL::USER, 1),
            'we have no rules, so only user admin has access.'
        );

        $this->getACL()->setObjectList('test/item', \Kryn\CmsBundle\ACL::GROUP, $group->getId(), true);
        $this->assertTrue(
            $this->getACL()->checkList('test/item', $item1->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId()),
            'testGroup got list access to all test/item objects.'
        );

        $this->getACL()->setObjectListExact('test/item', $item1->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId(), false);
        $this->assertFalse(
            $this->getACL()->checkList('test/item', $item1->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId()),
            'testGroup got list access-denied to item 1.'
        );

        $this->assertTrue(
            $this->getACL()->checkList('test/item', $item2->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId()),
            'testGroup still have access to item2.'
        );

        $this->getACL()->setObjectListExact('test/item', $item2->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId(), false);
        $this->assertFalse(
            $this->getACL()->checkList('test/item', $item2->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId()),
            'testGroup does not have access to item2 anymore.'
        );

        $acl = $this->getACL()->setObjectListExact('test/item', $item2->getId(), \Kryn\CmsBundle\ACL::USER, $user->getId(), true);
        $this->assertTrue(
            $this->getACL()->checkList('test/item', $item2->getId(), \Kryn\CmsBundle\ACL::USER, $user->getId()),
            'testUser got access through a rule for only him.'
        );

        $acl->setAccess(false);
        $acl->save();
        $this->getACL()->clearCache();
        $this->assertFalse(
            $this->getACL()->checkList('test/item', $item2->getId(), \Kryn\CmsBundle\ACL::USER, $user->getId()),
            'testUser got no-access through a rule for only him.'
        );

        //access to every item
        $acl = $this->getACL()->setObjectList('test/item', \Kryn\CmsBundle\ACL::GROUP, $group->getId(), true);
        $this->assertTrue(
            $this->getACL()->checkList('test/item', $item2->getId(), \Kryn\CmsBundle\ACL::USER, $user->getId()),
            'testUser has now access to all items through his group.'
        );
        $this->assertTrue(
            $this->getACL()->checkList('test/item', $item1->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId()),
            'testGroup has now access to all items.'
        );
        $this->assertTrue(
            $this->getACL()->checkList('test/item', $item2->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId()),
            'testGroup has now access to all items.'
        );

        //remove the acl item that gives access to anything.
        $acl->delete();
        $this->getACL()->clearCache();
        $this->assertFalse(
            $this->getACL()->checkList('test/item', $item2->getId(), \Kryn\CmsBundle\ACL::USER, $user->getId()),
            'testUser has no access anymore, since we deleted the access-for-all rule.'
        );
        $this->assertFalse(
            $this->getACL()->checkList('test/item', $item1->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId()),
            'testGroup has no access anymore to all items (item1).'
        );
        $this->assertFalse(
            $this->getACL()->checkList('test/item', $item2->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId()),
            'testGroup has no access anymore to all items (item2).'
        );

        //check checkListCondition
        $this->getACL()->setObjectListCondition(
            'test/item',
            array(array('id', '>', $item1->getId())),
            \Kryn\CmsBundle\ACL::GROUP,
            $group->getId(),
            true
        );
        $this->assertTrue(
            $this->getACL()->checkList('test/item', $item2->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId()),
            'testGroup has access to all items after item1'
        );

        $this->assertFalse(
            $this->getACL()->checkList('test/item', $item1->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId()),
            'testGroup has access to all items after item1, but only > , so not item1 itself.'
        );

        //revoke anything to object 'test\item'
        $this->getACL()->setObjectList('test/item', \Kryn\CmsBundle\ACL::GROUP, $group->getId(), false);
        $this->assertFalse(
            $this->getACL()->checkList('test/item', $item2->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId()),
            'testGroup has no access to all items after item1'
        );

        //check against object test
        $this->getACL()->setObjectListExact('test/test', $test1->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId(), true);
        $this->assertTrue(
            $this->getACL()->checkList('test/test', $test1->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId()),
            'testGroup has access test1.'
        );

        $this->getACL()->setObjectList('test/test', \Kryn\CmsBundle\ACL::GROUP, $group->getId(), false);
        $this->assertFalse(
            $this->getACL()->checkList('test/test', $test1->getId(), \Kryn\CmsBundle\ACL::GROUP, $group->getId()),
            'testGroup has no access test1.'
        );

        $this->getACL()->setCaching(true);
        $this->getACL()->removeObjectRules('test/item');
    }

}
