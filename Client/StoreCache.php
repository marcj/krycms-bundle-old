<?php

namespace Kryn\CmsBundle\Client;

use Kryn\CmsBundle\Configuration\SessionStorage;
use Kryn\CmsBundle\Model\Session;

class StoreCache extends AbstractStore
{
    /**
     * @var \Core\Cache\CacheInterface
     */
    protected $cache;

    function __construct(SessionStorage $storage, ClientAbstract $client)
    {
        parent::__construct($storage, $client);

        $clazz = $client->getClientConfig()->getSessionStorage()->getOption('class');
        $this->cache = new $clazz();
        if (!$this->cache instanceof \Core\Cache\CacheInterface) {
            throw new \LogicException('Session storage cache class not a instance of \Core\Cache\CacheInterface.');
        }
    }


    public function save($key, Session $session)
    {
        $cacheKey = $this->getClient()->getTokenId() . '_' . $key;
        return $this->cache->set(
            $cacheKey,
            $session->exportTo('JSON'),
            $this->getClient()->getConfig()['timeout']
        );
    }

    public function get($key)
    {
        $cacheKey = $this->getClient()->getTokenId() . '_' . $key;
        return $this->cache->get($cacheKey);
    }

    public function delete($key)
    {
        $cacheKey = $this->getClient()->getTokenId() . '_' . $key;
        $this->cache->delete($cacheKey);
    }

}