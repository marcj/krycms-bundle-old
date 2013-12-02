<?php

namespace Kryn\CmsBundle\Controller\Admin;

use Core\Kryn;
use Core\Models\Base\LanguageQuery;
use Core\SystemFile;
use Propel\Runtime\Map\TableMap;

class Config
{
    public static function getLabels()
    {
        $res['langs'] = LanguageQuery::create()
            ->orderByTitle()
            ->find()
            ->toArray(null, null, TableMap::TYPE_STUDLYPHPNAME);

        $res['timezones'] = timezone_identifiers_list();

        return $res;
    }

    public static function getConfig()
    {
        return Kryn::getSystemConfig()->toArray(true);
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
