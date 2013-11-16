<?php

namespace Kryn\CmsBundle\Client;

use Kryn\CmsBundle\Configuration\SessionStorage;
use Kryn\CmsBundle\Model\Session;

interface SessionStorageInterface
{
    function __construct(SessionStorage $storage, ClientAbstract $client);

    public function save($key, Session $session);

    public function get($key);

    public function delete($key);
}