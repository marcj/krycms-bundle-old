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
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class UIAssetsController extends Controller
{
    /**
     * @ApiDoc(
     *  section="Interface assets",
     *  description="Prints all possible language codes"
     * )
     *
     * @Rest\Get("/admin/ui/languages")
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

        $response = new Response("window.ka = window.ka || {}; ka.possibleLangs = " . $json.';');
        #$response->headers->set('Content-Type', 'text/javascript');
        return $response;
    }

    /**
     * @return Core
     */
    public function getKrynCore()
    {
        return $this->get('kryn_cms');
    }

    /**
     * @ApiDoc(
     *  section="Interface assets",
     *  description="Prints the language plural form"
     * )
     *
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2,3}", strict=true, description="The language code")
     *
     * @Rest\Get("/admin/ui/language-plural")
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
     * @ApiDoc(
     *  section="Interface assets",
     *  description="Prints all language messages"
     * )
     *
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2,3}", strict=true, description="The language code")
     * @Rest\QueryParam(name="javascript", requirements=".+", default=false, description="If it should be printed as javascript")
     *
     * @Rest\Get("/admin/ui/language")
     *
     * @param string $lang
     * @param string $javascript
     *
     * @return array|string depends on javascript param
     */
    public function getLanguageAction($lang, $javascript)
    {
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
