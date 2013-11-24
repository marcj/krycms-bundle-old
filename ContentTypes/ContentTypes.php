<?php

namespace Kryn\CmsBundle\ContentTypes;

use Kryn\CmsBundle\Core;

class ContentTypes {

    /**
     * @var AbstractType[]
     */
    protected $types;

    public function addType($alias, $contentType)
    {
        $this->types[$alias] = $contentType;
    }

}