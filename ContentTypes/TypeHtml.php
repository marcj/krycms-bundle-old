<?php

namespace Kryn\CmsBundle\ContentTypes;

class TypeHtml extends AbstractType
{
    public function render()
    {
        return $this->getContent()->getContent() ?: '';
    }
}