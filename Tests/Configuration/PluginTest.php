<?php

namespace Tests\Core;

use Kryn\CmsBundle\Configuration\Bundle;
use Kryn\CmsBundle\Configuration\Cache;
use Kryn\CmsBundle\Configuration\Client;
use Kryn\CmsBundle\Configuration\Database;
use Kryn\CmsBundle\Configuration\EntryPoint;
use Kryn\CmsBundle\Configuration\Errors;
use Kryn\CmsBundle\Configuration\Field;
use Kryn\CmsBundle\Configuration\FilePermission;
use Kryn\CmsBundle\Configuration\Object;
use Kryn\CmsBundle\Configuration\Plugin;
use Kryn\CmsBundle\Configuration\Route;
use Kryn\CmsBundle\Configuration\RouteDefault;
use Kryn\CmsBundle\Configuration\RouteRequirement;
use Kryn\CmsBundle\Configuration\SessionStorage;
use Kryn\CmsBundle\Configuration\SystemConfig;
use Kryn\CmsBundle\Configuration\Connection;
use Kryn\CmsBundle\Configuration\Theme;
use Kryn\CmsBundle\Configuration\ThemeContent;
use Kryn\CmsBundle\Configuration\ThemeLayout;
use Kryn\CmsBundle\Tests\KernelAwareTestCase;

class PluginTest extends KernelAwareTestCase
{
    private $xml = '<plugin id="listing">
  <label>News Listing</label>
  <class>Publication\Controller\Plugin\News</class>
  <method>listing</method>
  <routes>
    <route pattern="{page}">
      <default key="page">1</default>
      <requirement key="page">\d</requirement>
    </route>
    <route pattern="{slug}">
      <requirement key="page">[^/]+</requirement>
    </route>
  </routes>
  <options>
    <field id="template" type="view">
      <label>Template</label>
      <options>
        <option key="directory">@PublicationBundle/news/list/</option>
      </options>
    </field>
    <field id="itemsPerPage" type="number">
      <label>Items per page</label>
      <default>10</default>
    </field>
    <field id="detailPage" type="object">
      <label>Detail page</label>
      <object>KrynCmsBundle:Node</object>
    </field>
  </options>
</plugin>';
    public function testPluginConfig()
    {
        $plugin = new Plugin($this->xml, $this->getKrynCore());
        $this->valueTest($plugin);
    }

    public function valueTest(Plugin $plugin)
    {
        $this->assertEquals('listing', $plugin->getId());
        $this->assertEquals('News Listing', $plugin->getLabel());
        $this->assertEquals('Publication\Controller\Plugin\News', $plugin->getClass());
        $this->assertEquals('listing', $plugin->getMethod());

        $this->assertInstanceOf('\Kryn\CmsBundle\Configuration\Route', $plugin->getRoutes()[0]);
        $this->assertInstanceOf('\Kryn\CmsBundle\Configuration\Route', $plugin->getRoutes()[1]);

        $this->assertEquals('1', $plugin->getRoutes()[0]->getDefaultValue('page'));

        $this->assertEquals('Items per page', $plugin->getOption('itemsPerPage')->getLabel());
        $this->assertEquals('@PublicationBundle/news/list/', $plugin->getOption('template')->getOption('directory'));

        $array = $plugin->toArray();

        $this->assertEquals('listing', $array['id']);
        $this->assertEquals('News Listing', $array['label']);
        $this->assertEquals('Publication\Controller\Plugin\News', $array['class']);
        $this->assertEquals('listing', $array['method']);

        $this->assertCount(2, $array['routes']);
        $this->assertCount(3, $array['options']);

        $this->assertEquals([
            'pattern' => '{page}',
            'defaults' => [
                'page' => 1
            ],
            'requirements' => [
                'page' => '\\d'
            ]
        ], $array['routes'][0]);

        $this->assertEquals('Items per page', $array['options']['itemsPerPage']['label']);

        $this->assertEquals($this->xml, $plugin->toXml());
    }

    public function testPluginConfigPhp()
    {
        $plugin = new Plugin();
        $plugin->setId('listing');
        $plugin->setLabel('News Listing');
        $plugin->setClass('Publication\Controller\Plugin\News');
        $plugin->setMethod('listing');

        $route1 = new Route();
        $route1->setPattern('{page}');

        $route1->addDefault(new RouteDefault(['key' => 'page', 'value' => 1]));
        $route1->addRequirement(new RouteRequirement(['key' => 'page', 'value' => '\\d']));

        $plugin->addRoute($route1);

        $route2 = new Route(null, $this->getKrynCore());
        $route2->setPattern('{slug}');
        $route2->addRequirement(new RouteRequirement(['key' => 'page', 'value' => '[^/]+']));

        $plugin->addRoute($route2);

        $field1 = new Field(null, $this->getKrynCore());
        $field1->setId('template');
        $field1->setType('view');
        $field1->setLabel('Template');
        $field1->setOption('directory', '@PublicationBundle/news/list/');

        $field2 = new Field(null, $this->getKrynCore());
        $field2->setId('itemsPerPage');
        $field2->setType('number');
        $field2->setLabel('Items per page');
        $field2->setDefault(10);

        $field3 = new Field(null, $this->getKrynCore());
        $field3->setId('detailPage');
        $field3->setType('object');
        $field3->setLabel('Detail page');
        $field3->setObject('KrynCmsBundle:Node');

        $plugin->addOption($field1);
        $plugin->addOption($field2);
        $plugin->addOption($field3);

        $this->valueTest($plugin);
    }


    public function testPluginConfigArray()
    {
        $pluginArray = array (
            'id' => 'listing',
            'label' => 'News Listing',
            'class' => 'Publication\\Controller\\Plugin\\News',
            'method' => 'listing',
            'routes' => array (
                array (
                    'pattern' => '{page}',
                    'defaults' => array (
                        'page' => 1,
                    ),
                    'requirements' => array (
                        'page' => '\\d',
                    ),
                ),
                array (
                    'pattern' => '{slug}',
                    'requirements' => array (
                        'page' => '[^/]+',
                    ),
                ),
            ),
            'options' => array (
                'template' => array (
                    'id' => 'template',
                    'label' => 'Template',
                    'type' => 'view',
                    'options' => array (
                        'directory' => '@PublicationBundle/news/list/',
                    ),
                ),
                'itemsPerPage' => array (
                    'id' => 'itemsPerPage',
                    'label' => 'Items per page',
                    'type' => 'number',
                    'default' => 10,
                ),
                'detailPage' => array (
                    'id' => 'detailPage',
                    'label' => 'Detail page',
                    'type' => 'object',
                    'object' => 'KrynCmsBundle:Node',
                ),
            ),
        );

        $plugin = new Plugin($pluginArray);
        $this->assertEquals($pluginArray, $plugin->toArray());
        $this->valueTest($plugin);
    }
}
