<?php

namespace Kryn\CmsBundle\Controller;

use Kryn\CmsBundle\Controller;
use Kryn\CmsBundle\Model\Base\DomainQuery;
use Kryn\CmsBundle\Model\NodeQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Response;

class FrontendController extends Controller
{

    /**
     * @Route("/{uri}", requirements={"uri" = ".+"})
     */
    public function frontendAction($uri)
    {
        if (!$this->searchDomain()){
            throw new \Exception('Domain not found.');
        }
        if (!$this->searchPage($uri)){
            throw new \Exception('Page not found');
        }

        $pageResponse = $this->getKrynCore()->getPageResponse();

        $page = $this->getKrynCore()->getCurrentPage();
        $pageResponse->setTitle($page->getTitle());
        $pageResponse->renderContent();

        return $pageResponse; //new Response('<body>ho</body>');
    }

    protected function isEditMode()
    {
        return false;
    }

    /**
     * Reads the requested URL and try to extract the requested language.
     *
     * @return string Empty string if nothing found.
     * @internal
     */
    public function getPossibleLanguage()
    {
        $uri = $this->getRequest()->getRequestUri();

        if (strpos($uri, '/') > 0) {
            $first = substr($uri, 0, strpos($uri, '/'));
        } else {
            $first = $uri;
        }

        if ($this->isValidLanguage($first)) {
            return $first;
        }

        return '';
    }

    /**
     * Check whether specified pLang is a valid language
     *
     * @param string $lang
     *
     * @return bool
     * @internal
     */
    public function isValidLanguage($lang)
    {
        return false;
        //todo
//        if (!isset(Kryn::$config['languages']) && $lang == 'en') {
//            return true;
//        } //default
//
//        if (Kryn::$config['languages']) {
//            return array_search($lang, Kryn::$config['languages']) !== true;
//        } else {
//            return $lang == 'en';
//        }
    }

    public function searchDomain($noRefreshCache = false)
    {
        $request = $this->getRequest();
        $dispatcher = $this->getKrynCore()->getEventDispatcher();

        if ($this->isEditMode() && $domainId = $request->get('_kryn_editor_domain')) {
            $hostname = DomainQuery::create()->select('domain')->findPk($domainId);
        } else {
            $hostname = $request->get('_kryn_domain') ? : $request->getHost();
        }

        $domain = null;
        $possibleLanguage = $this->getPossibleLanguage();
        $hostnameWithLanguage = $hostname . '/' . $possibleLanguage;

        $cachedDomains = $this->getKrynCore()->getDistributedCache('core/domains');

        if ($cachedDomains) {
            $cachedDomains = @\unserialize($cachedDomains);
        }

        if (!is_array($cachedDomains)) {
            $cachedDomains = array();

            $domains = DomainQuery::create()->find();
            foreach ($domains as $domain) {
                $key = $domain->getDomain();
                $langKey = '';

                if (!$domain->getMaster()) {
                    $langKey = '/' . $domain->getLanguage();
                }

                $cachedDomains[$key . $langKey] = $domain;

                if ($domain->getRedirect()) {
                    $redirects = $domain->getRedirect();
                    $redirects = explode(',', str_replace(' ', '', $redirects));
                    foreach ($redirects as $redirectDomain) {
                        $cachedDomains['!redirects'][$redirectDomain . $langKey] = $key . $langKey;
                    }
                }

                if ($domain->getAlias()) {
                    $aliases = $domain->getAlias();
                    $aliases = explode(',', str_replace(' ', '', $aliases));
                    foreach ($aliases as $aliasDomain) {
                        $cachedDomains['!aliases'][$aliasDomain . $langKey] = $key . $langKey;
                    }
                }
            }

            $this->getKrynCore()->setDistributedCache('core/domains', serialize($cachedDomains));
        }

        //search redirect
        if (isset($cachedDomains['!redirects'])
            && (isset($cachedDomains['!redirects'][$hostnameWithLanguage]) && $redirectToDomain = $cachedDomains['!redirects'][$hostnameWithLanguage])
            || (isset($cachedDomains['!redirects'][$hostname]) && $redirectToDomain = $cachedDomains['!redirects'][$hostname])
        ) {
            $domain = $cachedDomains[$redirectToDomain];
            $dispatcher->dispatch('core/domain-redirect', new GenericEvent($domain));
            return null;
        }

        //search alias
        if (isset($cachedDomains['!aliases']) &&
            (($aliasHostname = $cachedDomains['!aliases'][$hostnameWithLanguage]) ||
            ($aliasHostname = $cachedDomains['!aliases'][$hostname]))
        ) {
            $domain = $cachedDomains[$aliasHostname];
            $hostname = $aliasHostname;
        } else if (isset($cachedDomains[$hostname])) {
            $domain = $cachedDomains[$hostname];
        }

        if (!$domain) {
            $dispatcher->dispatch('core/domain-not-found', new GenericEvent($hostname));
            return;
        }

        $this->getKrynCore()->setCurrentDomain($domain);
        $domain->setRealDomain($hostname);

        return $domain;
    }

    protected function searchPage($path)
    {
        $url = self::getRequest()->getPathInfo();

        $domain = $this->getKrynCore()->getCurrentDomain()->getId();
        $urls = $this->getKrynCore()->getUtils()->getCachedUrlToPage($domain);

        //extract extra url attributes
        $found = $end = false;
        $possibleUrl = $next = $url;
        $oriUrl = $possibleUrl;

        do {

            $id = isset($urls[$possibleUrl]) ? $urls[$possibleUrl] : 0;

            if ($id > 0 || $possibleUrl == '') {
                $found = true;
            } elseif (!$found) {
                $id = isset($urls['alias']) && isset($urls['alias'][$possibleUrl]) ? $urls['alias'][$possibleUrl] : 0;
                if ($id > 0) {
                    $found = true;
                    //we found a alias
                    die('redirect to ' . $id);
                    //Kryn::redirectToPage($id);
                } else {
                    $possibleUrl = $next;
                }
            }

            if ($next == false) {
                $end = true;
            } else {
                /*
                //maybe we found a alias in the parens with have a alias with "withsub"
                $aliasId = Kryn::$urls['alias'][$next . '/%'];

                if ($aliasId) {

                    //links5003/test => links5003_5/test

                    $aliasPageUrl = Kryn::$urls['id']['id=' . $aliasId];

                    $urlAddition = str_replace($next, $aliasPageUrl, $url);

                    $toUrl = $urlAddition;

                    //go out, and redirect the user to this url
                    Kryn::redirect($urlAddition);
                    $end = true;
                }
                */
            }

            $pos = strrpos($next, '/');
            if ($pos !== false) {
                $next = substr($next, 0, $pos);
            } else {
                $next = false;
            }

        } while (!$end);

        $diff = substr($url, strlen($possibleUrl), strlen($url));

        if (substr($diff, 0, 1) != '/') {
            $diff = '/' . $diff;
        }

        $extras = explode("/", $diff);
        if (count($extras) > 0) {
            foreach ($extras as $nr => $extra) {
                $_REQUEST['e' . $nr] = $extra;
            }
        }
        $url = $possibleUrl;

//        Kryn::$isStartpage = false;

        if ($url == '') {
            $pageId = $this->getKrynCore()->getCurrentDomain()->getStartnodeId();

            if (!$pageId > 0) {
                $this->getKrynCore()->getEventDispatcher()->dispatch('core/domain-no-start-page');
            }

//            Kryn::$isStartpage = true;
        } else {
            $pageId = $id;
        }

        $page = NodeQuery::create()
            ->findOneById($pageId);

        $this->getKrynCore()->setCurrentPage($page);

        return $page ? $pageId : null;
    }
}