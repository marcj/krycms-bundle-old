<?php

namespace Kryn\CmsBundle\AssetHandler;

class CssHandler extends AbstractHandler implements LoaderHandlerInterface
{
    protected function getTag(AssetInfo $assetInfo)
    {
        if ($assetInfo->getFile()) {
            return sprintf('<link rel="stylesheet" type="text/css" href="%s" >', $this->getPublicAssetPath($assetInfo->getFile()));
        } else {
            return sprintf(<<<EOF
<style type="text/css">
%s
</style>
EOF
            , $assetInfo->getContent());
        }
    }

    public function getTags(array $assetInfo = array(), $concatenation = false)
    {
        $tags = [];

        if ($concatenation) {
            foreach ($assetInfo as $asset) {
                //todo concat
                $tags[] = $this->getTag($asset);
            }
        } else {
            foreach ($assetInfo as $asset) {
                $tags[] = $this->getTag($asset);
            }
        }

        return implode("\n", $tags);
    }

}