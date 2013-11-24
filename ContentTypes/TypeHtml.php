<?php

namespace Kryn\CmsBundle\ContentTypes;

use Kryn\CmsBundle\Model\Content;

class TypeHtml extends AbstractType
{
    public function render()
    {
        return $this->getContent()->getContent() ?: '';
    }
}