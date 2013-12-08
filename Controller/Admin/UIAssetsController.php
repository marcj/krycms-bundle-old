<?php

namespace Kryn\CmsBundle\Controller\Admin;

use FOS\RestBundle\Request\ParamFetcher;
use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Model\LanguageQuery;
use Propel\Runtime\Map\TableMap;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;

class UIAssetsController extends Controller
{
    /**
     * @Rest\Get("ui/languages")
     *
     * @return string javascript
     */
    public function getPossibleLangs()
    {
        $languages = LanguageQuery::create()
            ->filterByVisible(true)
            ->orderByCode()
            ->find()
            ->toArray('Code', null, TableMap::TYPE_STUDLYPHPNAME);

        if (0 === count($languages)) {
            $json = '{"en":{"code":"en","title":"English","langtitle":"English"}}';
        } else {
            $json = json_encode($languages);
        }

        header('Content-Type: text/javascript');
        print "window.ka = window.ka || {}; ka.possibleLangs = " . $json.';';
        exit;
    }

    /**
     * @return Core
     */
    public function getKrynCore()
    {
        return $this->get('kryn.cms');
    }

    /**
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2,3}", strict=true, description="The language code")
     *
     * @Rest\Get("ui/language-plural")
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return string javascript
     */
    public function getLanguagePluralForm(ParamFetcher $paramFetcher)
    {
        $lang = $paramFetcher->get('lang');

        $lang = preg_replace('/[^a-z]/', '', $lang);
        $file = $this->getKrynCore()->getTranslator()->getPluralJsFunctionFile($lang); //just make sure the file has been created
        header('Content-Type: text/javascript');
        $fs = $this->getKrynCore()->getWebFileSystem();
        echo $fs->read($file);
        exit;
    }

    /**
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2,3}", strict=true, description="The language code")
     * @Rest\QueryParam(name="javascript", requirements=".+", default=false, description="If it should be printed as javascript")
     *
     * @Rest\View()
     * @Rest\Get("ui/language")
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return array|string depends on javascript param
     */
    public function getLanguageAction(ParamFetcher $paramFetcher)
    {
        $lang = $paramFetcher->get('lang');
        $javascript = filter_var($paramFetcher->get('javascript'), FILTER_VALIDATE_BOOLEAN);

        if (!$this->getKrynCore()->getTranslator()->isValidLanguage($lang)) {
            $lang = 'en';
        }

        $this->getKrynCore()->getAdminClient()->getSession()->setLanguage($lang);
        $this->getKrynCore()->getAdminClient()->syncStore();

        $messages = $this->getKrynCore()->getTranslator()->loadMessages($lang);
        $template = $this->getKrynCore()->getTemplating();

        if ($javascript) {
            header('Content-Type: text/javascript');
            print "if( typeof(ka)=='undefined') window.ka = {}; ka.lang = " . json_encode($messages, JSON_PRETTY_PRINT);
            print "\nLocale.define('en-US', 'Date', " . $template->render(
                'KrynCmsBundle:Default:javascript-locales.js.twig'
            ) . ");";
            exit;
        } else {
            $messages['mootools'] = $template->render('KrynCmsBundle:Default:javascript-locales.js.twig');

            return $messages;
        }
    }
}
