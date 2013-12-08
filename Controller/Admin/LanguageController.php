<?php

namespace Kryn\CmsBundle\Controller\Admin;

use FOS\RestBundle\Request\ParamFetcher;
use Kryn\CmsBundle\Controller;
use Kryn\CmsBundle\Controller\Admin\BundleManager\ManagerController;
use FOS\RestBundle\Controller\Annotations as Rest;

class LanguageController extends Controller
{

    /**
     * Returns all language messages + pluralCount and pluralForm.
     *
     * @Rest\QueryParam(name="bundle", requirements=".+", strict=true, description="The bundle name")
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2,3}", strict=true, description="The language code")
     *
     * @Rest\View()
     * @Rest\Get("/admin/system/bundle/editor/language")
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return array
     */
    public function getLanguageAction(ParamFetcher $paramFetcher)
    {
        $bundle = $paramFetcher->get('bundle');
        $lang = $paramFetcher->get('lang');

        return $this->getLanguage($bundle, $lang);
    }

    /**
     * @param string $bundle
     * @param string $lang
     * @return array
     */
    protected function getLanguage($bundle, $lang)
    {
        ManagerController::prepareName($bundle);
        $utils = $this->getTranslator()->getUtils();

        $file = $this->getKrynCore()->getBundleDir($bundle) . "Resources/translations/$lang.po";
        $res = $utils->parsePo($file);

        $pluralForm = $utils->getPluralForm($lang);
        preg_match('/^nplurals=([0-9]+);/', $pluralForm, $match);

        $res['pluralCount'] = intval($match[1]);
        $res['pluralForm'] = $pluralForm;

        return $res;
    }

    /**
     * Saves language messages.
     *
     * @Rest\QueryParam(name="bundle", requirements=".+", strict=true, description="The bundle name")
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2,3}", strict=true, description="The language code")
     * @Rest\RequestParam(name="langs", array=true, description="The language messages")
     *
     * @Rest\View()
     * @Rest\Post("/admin/system/bundle/editor/language")
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return bool
     */
    public function setLanguageAction(ParamFetcher $paramFetcher)
    {
        $bundle = $paramFetcher->get('bundle');
        $lang = $paramFetcher->get('lang');
        $langs = $paramFetcher->get('langs');

        ManagerController::prepareName($bundle);
        $utils = $this->getTranslator()->getUtils();
        return $utils->saveLanguage($bundle, $lang, $langs);
    }

    /**
     * Extras all language messages in the given bundle.
     *
     * @Rest\QueryParam(name="bundle", requirements=".+", strict=true, description="The bundle name")
     *
     * @Rest\View()
     * @Rest\Get("/admin/system/bundle/editor/extract")
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return array
     */
    public function getExtractedLanguageAction(ParamFetcher $paramFetcher)
    {
        $bundle = $paramFetcher->get('bundle');
        ManagerController::prepareName($bundle);

        $utils = $this->getTranslator()->getUtils();
        return $utils->extractLanguage($bundle);
    }

    /**
     * Gets a overview of translated messages.
     *
     * @Rest\QueryParam(name="bundle", requirements=".+", strict=true, description="The bundle name")
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2,3}", strict=true, description="The language code")
     *
     * @Rest\View()
     * @Rest\Get("/admin/system/bundle/editor/overview")
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return array[count => int, countTranslated => int]
     */
    public function getOverviewExtractAction(ParamFetcher $paramFetcher)
    {
        $bundle = $paramFetcher->get('bundle');
        $lang = $paramFetcher->get('lang');

        $utils = $this->getTranslator()->getUtils();
        $extract = $utils->extractLanguage($bundle);
        $translated = $this->getLanguage($bundle, $lang);

        $p100 = count($extract);
        $cTranslated = 0;

        foreach ($extract as $id => $translation) {
            if (isset($translated['translations'][$id]) && $translated['translations'][$id] != '') {
                $cTranslated++;
            }
        }

        return array(
            'count' => $p100,
            'countTranslated' => $cTranslated
        );
    }

    public function getAllLanguages($lang = 'en')
    {
        if ($lang == '') {
            $lang = 'en';
        }

        $res = array();
        $utils = $this->getTranslator()->getUtils();

        foreach ($this->getKrynCore()->getConfigs() as $key => $mod) {

            $res[$key]['config'] = $mod;
            $res[$key]['lang'] = $utils->extractLanguage($key);

            if (count($res[$key]['lang']) > 0) {
                $translate = $this->getLanguage($key, $lang);
                foreach ($res[$key]['lang'] as $skey => &$lang2) {
                    if ($translate[$skey] != '') {
                        $lang2 = $translate[$skey];
                    } else {
                        $lang2 = '';
                    }
                }
            }
        }

        return $res;

    }

}
