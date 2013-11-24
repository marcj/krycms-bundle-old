<?php

namespace Kryn\CmsBundle\ContentTypes;

use Kryn\CmsBundle\Model\Content;
use Core\WebFile;

class TypeImage extends AbstractType
{
    public function render()
    {
        if ($this->getContent()->getContent()) {
            $info = json_decode($this->getContent()->getContent(), true);
            if ($info['file']) {
                $path = substr(is_numeric($info['file']) ? WebFile::getPath($info['file']) : $info['file'], 1);
                $width = $info['width'] ?: '100%';
                $class = 'ka-contentType-image align-' . ($info['align'] ?: 'center');
                return sprintf('<div class="%s"><img src="%s" width="%s"/></div>', $class, $path, $width);
            }
        }
    }
}