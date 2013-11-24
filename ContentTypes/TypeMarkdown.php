<?php

namespace Kryn\CmsBundle\ContentTypes;

use Kryn\CmsBundle\Model\Content;
use Core\WebFile;

use Michelf\MarkdownExtra;

class TypeMarkdown extends AbstractType
{
    public function render()
    {
        if ($content = $this->getContent()->getContent()) {
            return MarkdownExtra::defaultTransform($content);
        }
    }
}