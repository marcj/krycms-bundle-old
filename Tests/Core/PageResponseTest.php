<?php

namespace Kryn\CmsBundle\Tests\Core;

use Kryn\CmsBundle\Tests\KernelAwareTestCase;

class PageResponseTest extends KernelAwareTestCase
{
    public function testJsAssets()
    {
        $response = $this->getPageResponse();
        $response->addJsFile('@TestBundle/page-response-test/javascript1.js');
        $response->addJs("var c = 'cde'");
        $response->addJsFile('@TestBundle/page-response-test/javascript2.js');

        $assetsTags = $response->getAssetTags();
        $expected = <<<EOF
<script type="text/javascript" src="bundles/test/page-response-test/javascript1.js"></script>
<script type="text/javascript">
var c = 'cde'
</script>
<script type="text/javascript" src="bundles/test/page-response-test/javascript2.js"></script>
EOF;

        $this->assertEquals($expected, $assetsTags['jsTags']);
    }

    public function testJsAssetsCompressed()
    {
        $response = $this->getPageResponse();
        $response->addJsFile('@TestBundle/page-response-test/javascript1.js');
        $response->addJs("var c = 'cde'");
        $response->addJsFile('@TestBundle/page-response-test/javascript2.js');
        $response->setResourceCompression(true);

        $assetsTags = $response->getAssetTags();
        $expected = <<<EOF
<script type="text/javascript">
var c = 'cde'
</script>
<script type="text/javascript" src="cache/compressed-([a-z0-9]{32}).js"></script>
EOF;

        $this->assertRegExp("#$expected#", $assetsTags['jsTags']);

        preg_match('#compressed-([a-z0-9]{32}).js#', $assetsTags['jsTags'], $matches);
        $compressedFile = 'cache/compressed-' . $matches[1] . '.js';

        $expectedCompressed = <<<EOF

/* @TestBundle/page-response-test/javascript1.js */

var a = 'abc';
/* @TestBundle/page-response-test/javascript2.js */

var b = 'cbd';
EOF;

        $compressedContent = $this->getWebFileSystem()->read($compressedFile);
        $this->assertContains($expectedCompressed, $compressedContent);
    }

    public function testCssAssets()
    {
        $response = $this->getPageResponse();
        $response->addCssFile('@TestBundle/page-response-test/style1.css');
        $response->addCss("body {font-size: 12px;}");
        $response->addCssFile('@TestBundle/page-response-test/style2.css');


        $assetsTags = $response->getAssetTags();
        $expected = <<<EOF
<link rel="stylesheet" type="text/css" href="bundles/test/page-response-test/style1.css" >
<style type="text/css">
body {font-size: 12px;}
</style>
<link rel="stylesheet" type="text/css" href="bundles/test/page-response-test/style2.css" >
EOF;

        $this->assertEquals($expected, $assetsTags['cssTags']);
    }

    public function testCssAssetsCompressed()
    {
        $response = $this->getPageResponse();
        $response->addCssFile('@TestBundle/page-response-test/style1.css');
        $response->addCss("body {font-size: 12px;}");
        $response->addCssFile('@TestBundle/page-response-test/style2.css');
        $response->setResourceCompression(true);

        $assetsTags = $response->getAssetTags();
        $expected = <<<EOF
<style type="text/css">
body {font-size: 12px;}
</style>
<link rel="stylesheet" type="text/css" href="cache/compressed-([a-z0-9]{32}).css" >
EOF;

        $this->assertRegExp("#$expected#", $assetsTags['cssTags']);
    }

}