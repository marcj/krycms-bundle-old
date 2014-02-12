<?php

namespace Kryn\CmsBundle\Tests\Service\Object;

use Kryn\CmsBundle\Propel\StandardEnglishPluralizer;
use Kryn\CmsBundle\Tests\KernelAwareTestCase;
use Test\Model\ItemCategoryQuery;
use Test\Model\ItemQuery;

class RelationTest extends KernelAwareTestCase
{
    public function testManyToOneVirtualField()
    {
        $testItemCategory = $this->getKrynCore()->getObjects()->getDefinition('test/itemCategory');
        $field = $testItemCategory->getField('items');
        $this->assertInstanceOf('Kryn\CmsBundle\Configuration\Field', $field);
        $this->assertEquals('items', $field->getId());
        $this->assertTrue($field->isVirtual());
        $this->assertEquals('Auto Object Relation (test/itemCategory)', $field->getLabel());
    }

    public function getPluralizerData()
    {
        return [
            ['sheep', 'sheep'],
            ['children', 'child'],
            ['aliases', 'alias'],
            ['asses', 'ass'],
            ['categories', 'category'],
            ['albums', 'album'],
            ['Employees', 'Employee'],
        ];
    }

    /**
     * @dataProvider getPluralizerData
     */
    public function testSingulizer($plural, $singular)
    {
        $pluralizer = new StandardEnglishPluralizer();
        $this->assertEquals($singular, $pluralizer->getSingularForm($plural));
    }

    /**
     * @dataProvider getPluralizerData
     */
    public function testPluralizer($plural, $singular)
    {
        $pluralizer = new StandardEnglishPluralizer();
        $this->assertEquals($plural, $pluralizer->getPluralForm($singular));
    }
    public function testPropelRefName()
    {
        $reflection = new \ReflectionClass('Test\Model\Item');
        $this->assertTrue($reflection->hasMethod('getCategories'), 'Item has getCategories');
        $this->assertTrue($reflection->hasMethod('getOneCategory'), 'Item has getOneCategory');

        $reflection = new \ReflectionClass('Test\Model\ItemCategory');
        $this->assertTrue($reflection->hasMethod('getItems')); //<objectRefRelationName>Items</objectRefRelationName>
        $this->assertTrue($reflection->hasMethod('getCrossItems')); //<objectRefRelationName>CrossItems</objectRefRelationName>
    }

    public function testManyToOne()
    {
        ItemQuery::create()->deleteAll();
        ItemCategoryQuery::create()->deleteAll();

        $id = uniqid();

        $newItemCategory = [
            'name' => 'Test Category ' . $id,
            'items' => [
                ['title' => 'Test Item ' . $id. ' 1'],
                ['title' => 'Test Item ' . $id. ' 2']
            ]
        ];

        $added = $this->getKrynCore()->getObjects()->add('test/itemCategory', $newItemCategory);

        $this->assertGreaterThan(0, $added['id']);

        $category = ItemCategoryQuery::create()->findOneById($added['id']);
        $items = $category->getItems();

        $this->assertCount(2, $items);

        $this->assertEquals('Test Item ' . $id. ' 1', $items[0]->getTitle());
        $this->assertEquals('Test Item ' . $id. ' 2', $items[1]->getTitle());
    }
}
