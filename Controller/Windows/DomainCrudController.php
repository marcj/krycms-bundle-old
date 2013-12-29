<?php

namespace Kryn\CmsBundle\Controller\Windows;
 
class DomainCrudController extends \Kryn\CmsBundle\Controller\WindowController {

    public $fields = array (
  '__General__' => array (
    'label' => 'General',
    'type' => 'tab',
    'children' => array (
      'domain' => array (
        'type' => 'predefined',
        'object' => 'KrynCmsBundle:Domain',
        'field' => 'domain',
      ),
      'path' => array (
        'type' => 'predefined',
        'object' => 'KrynCmsBundle:Domain',
        'field' => 'path',
      ),
      'lang' => array (
        'type' => 'predefined',
        'object' => 'kryncms/domain',
        'field' => 'lang',
      ),
      'master' => array (
        'type' => 'predefined',
        'object' => 'KrynCmsBundle:Domain',
        'field' => 'master',
      ),
      'startnode' => array (
        'type' => 'predefined',
        'object' => 'kryncms/domain',
        'field' => 'startnode',
      ),
      'theme' => array (
        'type' => 'predefined',
        'object' => 'kryncms/domain',
        'field' => 'theme',
      ),
    ),
    'key' => '__General__',
  ),
  '__Extra__' => array (
    'label' => 'Extra',
    'type' => 'tab',
    'children' => array (
      'resourceCompression' => array (
        'type' => 'predefined',
        'object' => 'KrynCmsBundle:Domain',
        'field' => 'resourceCompression',
      ),
      'favicon' => array (
        'type' => 'predefined',
        'object' => 'KrynCmsBundle:Domain',
        'field' => 'favicon',
      ),
      'robots' => array (
        'type' => 'predefined',
        'object' => 'KrynCmsBundle:Domain',
        'field' => 'robots',
      ),
      'email' => array (
        'type' => 'predefined',
        'object' => 'KrynCmsBundle:Domain',
        'field' => 'email',
      ),
      'alias' => array (
        'type' => 'predefined',
        'object' => 'kryncms/domain',
        'field' => 'alias',
      ),
      'redirect' => array (
        'type' => 'predefined',
        'object' => 'kryncms/domain',
        'field' => 'redirect',
      ),
    ),
    'key' => '__Extra__',
  ),
);

    public $defaultLimit = 15;

    public $add = false;

    public $edit = false;

    public $remove = false;

    public $nestedRootAdd = false;

    public $nestedRootEdit = false;

    public $nestedRootRemove = false;

    public $export = false;

    public $object = 'kryncms/domain';

    public $preview = false;

    public $titleField = 'domain';

    public $workspace = true;

    public $multiLanguage = true;

    public $multiDomain = false;


}
