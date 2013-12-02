<?php

namespace Kryn\CmsBundle\Controller\Admin;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Model\LanguageQuery;
use Propel\Runtime\Map\TableMap;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class UIAssets extends Controller
{
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
        print "window.ka = window.ka || {}; ka.possibleLangs = " . $json;
        exit;
    }

    /**
     * @return Core
     */
    public function getKrynCore()
    {
        return $this->get('kryn.cms');
    }

    public function getLanguagePluralForm($lang)
    {
        $lang = preg_replace('/[^a-z]/', '', $lang);
        $file = $this->getKrynCore()->getTranslator()->getPluralJsFunctionFile($lang); //just make sure the file has been created
        header('Content-Type: text/javascript');
        $fs = $this->getKrynCore()->getWebFileSystem();
        echo $fs->read($file);
        exit;
    }

    public function getLanguage($lang, $javascript = false)
    {
        $lang = substr($lang, 0, 4);

        if (!$this->getKrynCore()->getTranslator()->isValidLanguage($lang)) {
            $lang = 'en';
        }

        $this->getKrynCore()->getAdminClient()->getSession()->setLanguage($lang);
        $this->getKrynCore()->getAdminClient()->syncStore();

//        Kryn::loadLanguage($lang);

        $messages = $this->getKrynCore()->getTranslator()->loadMessages($lang);
        $template = $this->getKrynCore()->getTemplating();

        if ($javascript) {
            header('Content-Type: text/javascript');
            print "if( typeof(ka)=='undefined') window.ka = {}; ka.lang = " . json_encode($messages);
            print "\nLocale.define('en-US', 'Date', " . $template->render(
                'KrynCmsBundle:Default:javascript-locales.js.twig'
            ) . ");";
            exit;
        } else {
            $messages['mootools'] = $template->render('KrynCmsBundle:Default:javascript-locales.js.twig');

            return $messages;
        }
    }

    public static function collectFiles($array, &$files)
    {
        foreach ($array as $jsFile) {
            if (strpos($jsFile, '*') !== -1) {
                $folderFiles = find(PATH_WEB . $jsFile, false);
                foreach ($folderFiles as $file) {
                    if (!array_search($file, $files)) {
                        $files[] = $file;
                    }
                }
            } else {
                if (file_exists($jsFile)) {
                    $files[] = $jsFile;
                }
            }
        }

    }
}
