<?php

namespace Kryn\CmsBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;


class WindowNestedController extends NestedObjectCrudController {
    use WindowControllerTrait;
} 