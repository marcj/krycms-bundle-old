<?php

namespace Kryn\CmsBundle\ContentTypes;

use Kryn\CmsBundle\Model\Content;
use Kryn\CmsBundle\Core;

abstract class AbstractType implements TypeInterface
{
    /**
     * @var Content
     */
    private $content;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var Core
     */
    protected $krynCore;

    /**
     * @param Core $krynCore
     */
    public function setKrynCore($krynCore)
    {
        $this->krynCore = $krynCore;
    }

    /**
     * @return Core
     */
    public function getKrynCore()
    {
        return $this->krynCore;
    }

    /**
     * @param Content $content
     */
    public function setContent(Content $content)
    {
        $this->content = $content;
    }

    /**
     * @return Content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
