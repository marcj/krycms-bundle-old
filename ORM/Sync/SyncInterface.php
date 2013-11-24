<?php

namespace Kryn\CmsBundle\ORM\Sync;

use Kryn\CmsBundle\Bundle;
use Kryn\CmsBundle\Configuration\Object;

interface SyncInterface {
    public function syncObject(Bundle $bundle, Object $object);
}