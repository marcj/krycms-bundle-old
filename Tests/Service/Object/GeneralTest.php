<?php

namespace Kryn\CmsBundle\Tests\Service\Object;

use Kryn\CmsBundle\Tests\KernelAwareTestCase;

class GeneralTest extends KernelAwareTestCase
{
    public function testObject()
    {
        $definition = $this->getObjects()->getDefinition('Test\\Test');
        $this->assertNotEmpty($definition);
        $this->assertInstanceOf('Kryn\CmsBundle\Configuration\Object', $definition);

        $this->assertEquals('Test', $definition->getId());
        $this->assertEquals('name', $definition->getLabel());

        $objectClass = $this->getObjects()->getClass('Test\\Test');
        $this->assertNotEmpty($objectClass);
        $this->assertInstanceOf('Kryn\CmsBundle\ORM\ORMAbstract', $objectClass);
    }
}
