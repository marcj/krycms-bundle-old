<?php

namespace Kryn\CmsBundle\Controller\Admin;

use Kryn\CmsBundle\Controller;
use Kryn\CmsBundle\Controller\Admin\BundleManager\Manager;

class Languages extends Controller
{

    public function getLanguage($bundle, $lang = null)
    {
        Manager::prepareName($bundle);
        $utils = $this->getTranslator()->getUtils();

        $file = $this->getKrynCore()->getBundleDir($bundle) . "Resources/translations/$lang.po";
        $res = $utils->parsePo($file);

        $pluralForm = $utils->getPluralForm($lang);
        preg_match('/^nplurals=([0-9]+);/', $pluralForm, $match);

        $res['pluralCount'] = intval($match[1]);
        $res['pluralForm'] = $pluralForm;

        return $res;
    }

    public function saveLanguage($bundle, $langs, $lang = null)
    {
        Manager::prepareName($bundle);
        return \Core\Lang::saveLanguage($bundle, $lang, $langs);
    }

    public function getExtractedLanguage($bundle)
    {
        Manager::prepareName($bundle);

        return \Core\Lang::extractLanguage($bundle);
    }

    public function getOverviewExtract($bundle, $lang)
    {
        if (!$bundle || !$lang) {
            return array();
        }

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
                foreach ($res[$key]['lang'] as $key => &$lang2) {
                    if ($translate[$key] != '') {
                        $lang2 = $translate[$key];
                    } else {
                        $lang2 = '';
                    }
                }
            }
        }

        return $res;

    }

}
