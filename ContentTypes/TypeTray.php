<?php

namespace Kryn\CmsBundle\ContentTypes;

class TypeTray extends AbstractType
{
    /**
     * @var \Kryn\CmsBundle\ContentRender
     */
    protected $contentRenderer;

    function __construct($contentRenderer)
    {
        $this->contentRenderer = $contentRenderer;
    }

    public function render()
    {
        if ($this->getContent()->getContent()) {
            $value = json_decode($this->getContent()->getContent(), true);
            $nodeId = $value['node']+0;
            return $this->contentRenderer->renderSlot($nodeId);
        }
    }
}