<?php

namespace Kryn\CmsBundle\ORM\Builder;

use Kryn\CmsBundle\Configuration\Object;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

interface BuildInterface {
    public function build(Object $object);
}