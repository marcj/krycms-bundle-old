<?php

namespace Kryn\CmsBundle\ContentTypes;

use Kryn\CmsBundle\Model\Content;

class TypeText extends AbstractType
{
    public function render()
    {
        return $this->getContent()->getContent();
    }

}