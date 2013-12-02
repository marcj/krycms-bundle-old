<?php

namespace Kryn\CmsBundle\Controller\Admin;

class Languages
{

    public function getLanguage($bundle, $lang = null)
    {
        Manager::prepareName($bundle);
        return \Core\Lang::getLanguage($bundle, $lang);
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

        $extract = \Core\Lang::extractLanguage($bundle);
        $translated = \Core\Lang::getLanguage($bundle, $lang);

        $p100 = count($extract);
        $cTranslated = 0;

        foreach ($extract as $id => $translation) {
            if ($translated['translations'][$id] && $translated['translations'][$id] != '') {
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
        foreach (kryn::$configs as $key => $mod) {

            $res[$key]['config'] = $mod;
            $res[$key]['lang'] = \Core\Lang::extractLanguage($key);

            if (count($res[$key]['lang']) > 0) {
                $translate = \Core\Lang::getLanguage($key, $lang);
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
