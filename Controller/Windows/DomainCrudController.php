<?php

namespace Kryn\CmsBundle\Controller\Windows;

use Kryn\CmsBundle\Controller\WindowController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * AUTO-GENERATED THROUGH KRYN WINDOW-EDITOR
 */
class DomainCrudController extends WindowController
{
    public $fields = array(
        '__General__' => array(
            'label' => 'General',
            'type' => 'tab',
            'children' => array(
                'domain' => array(
                    'type' => 'predefined'
                ),
                'path' => array(
                    'type' => 'predefined'
                ),
                'master' => array(
                    'type' => 'predefined'
                ),
                'startnodeId' => array(
                    'type' => 'predefined'
                ),
                'alias' => array(
                    'type' => 'predefined'
                ),
                'redirect' => array(
                    'type' => 'predefined'
                ),
            ),
        ),
        '__Extra__' => array(
            'label' => 'Extra',
            'type' => 'tab',
            'children' => array(
                'resourceCompression' => array(
                    'type' => 'predefined'
                ),
                'favicon' => array(
                    'type' => 'predefined'
                ),
                'robots' => array(
                    'type' => 'predefined'
                ),
                'email' => array(
                    'type' => 'predefined'
                ),
            )
        )
    );

    public $defaultLimit = 15;

    public $add = false;

    public $edit = false;

    public $remove = false;

    public $nestedRootAdd = false;

    public $nestedRootEdit = false;

    public $nestedRootRemove = false;

    public $export = false;

    public $object = 'KrynCmsBundle:Domain';

    public $preview = false;

    public $titleField = 'domain';

    public $workspace = true;

    public $multiLanguage = true;

    public $multiDomain = false;

    public $versioning = false;

}
