<?php

namespace Kryn\CmsBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;


class WindowController extends ObjectCrudController {
    use WindowControllerTrait;
} 