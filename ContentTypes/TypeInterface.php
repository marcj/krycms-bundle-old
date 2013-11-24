<?php

namespace Kryn\CmsBundle\ContentTypes;

use Kryn\CmsBundle\Model\Content;

interface TypeInterface
{
    /**
     * @return string
     */
    public function render();

    public function setContent(Content $content);

    public function setParameters(array $parameters);

}