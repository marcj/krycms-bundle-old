<?php

namespace Kryn\CmsBundle\AssetHandler;

use Kryn\CmsBundle\File\FileInfo;

class JsHandler extends AbstractHandler implements LoaderHandlerInterface
{
    protected function getTag(AssetInfo $assetInfo)
    {
        if ($assetInfo->getFile()) {
            return sprintf('<script type="text/javascript" src="%s"></script>', $this->getPublicAssetPath($assetInfo->getFile()));
        } else {
            return sprintf(<<<EOF
<script type="text/javascript">
%s
</script>
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