<?php

namespace Kryn\CmsBundle\Tests\Service\Object;

use Kryn\CmsBundle\Propel\WorkspaceManager;
use Kryn\CmsBundle\Tests\KernelAwareTestCase;
use Test\Model\ItemCategoryQuery;
use Test\Model\ItemQuery;

class WorkspaceObjectRelationTest extends KernelAwareTestCase
{
    public function testThroughPropel()
    {
        WorkspaceManager::setCurrent(0);

        ItemQuery::create()->deleteAll();
        ItemCategoryQuery::create()->deleteAll();

        WorkspaceManager::setCurrent(0);
        //todo
    }

}
