<?php

namespace Kryn\CmsBundle\Filesystem\Adapter;

trait AdapterTrait {

    /**
     * @var string
     */
    protected $mountPath;

    /**
     * @param string $mountPath
     */
    public function setMountPath($mountPath)
    {
        $this->mountPath = $mountPath;
    }

    /**
     * @return string
     */
    public function getMountPath()
    {
        return $this->mountPath;
    }

}