<?php

namespace Kryn\CmsBundle\Controller\Admin;

use Kryn\CmsBundle\Controller;
use Kryn\CmsBundle\Model\LanguageQuery;
use Propel\Runtime\Map\TableMap;

class Config extends Controller
{
    public function getLabels()
    {
        $res['langs'] = LanguageQuery::create()
            ->orderByTitle()
            ->find()
            ->toArray(null, null, TableMap::TYPE_STUDLYPHPNAME);

        $res['timezones'] = timezone_identifiers_list();

        return $res;
    }

    public function getConfig()
    {
        return $this->getKrynCore()->getSystemConfig()->toArray(true);
    }

    public static function saveConfig()
    {
        //todo;
//        $cfg = include 'Config.php';
//
//        $blacklist[] = 'passwd_hash_key';
//
//        if (!getArgv('sessiontime')) {
//            $_REQUEST['sessiontime'] = 3600;
//        }
//
//        foreach ($_POST as $key => $value) {
//            if (!in_array($key, $blacklist)) {
//                $cfg[$key] = getArgv($key);
//            }
//        }
//
//        SystemFile::setContent('config.php', "<?php return " . var_export($cfg, true) . "\n? >");
//
//        dbUpdate('system_langs', array('visible' => 1), array('visible' => 0));
//        $langs = getArgv('languages');
//        foreach ($langs as $l) {
//            dbUpdate('system_langs', array('code' => $l), array('visible' => 1));
//        }

        return true;
    }


}
