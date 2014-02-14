<?php

namespace Kryn\CmsBundle\AssetHandler;

interface LoaderHandlerInterface
{
    /**
     * @param AssetInfo[] $assetInfo
     * @param bool        $concatenation
     * @return string
     */
    public function getTags(array $assetInfo = array(), $concatenation = false);
}