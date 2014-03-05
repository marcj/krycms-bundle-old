<?php

namespace Kryn\CmsBundle\Tests\Core;

use Kryn\CmsBundle\Tests\TestCase;
use Kryn\CmsBundle\Tools;

class ToolsTest extends TestCase
{
    public function testRelativePath()
    {
        $relative = Tools::getRelativePath('/anotherroot/web/file', '/root/web/other/dir');
        $this->assertEquals('../../../../anotherroot/web/file', $relative);

        $relative = Tools::getRelativePath('/root/web/file', '/root/web/other/dir');
        $this->assertEquals('../../file', $relative);

        $relative = Tools::getRelativePath('/root/web/dir/file/', '/root/web/dir');
        $this->assertEquals('file', $relative);

        $relative = Tools::getRelativePath('/root/web/other/file/', '/root/web/dir');
        $this->assertEquals('../other/file', $relative);
    }

    public function testUrlEncode()
    {
        $encoded = Tools::urlEncode('path/to/test');
        $this->assertEquals('path%252Fto%252Ftest', $encoded);
    }
}