<?php

namespace Kryn\CmsBundle\ORM\Sync;

use Kryn\CmsBundle\Configuration\Object;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

interface SyncInterface {
    public function syncObject(BundleInterface $bundle, Object $object);
}