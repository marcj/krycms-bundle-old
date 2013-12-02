<?php

namespace Kryn\CmsBundle\Translation;

use Kryn\CmsBundle\Core;

class Translator implements TranslationInterface
{
    protected $messages = [];

    /**
     * @var Core
     */
    protected $krynCore;

    function __construct(Core $krynCore)
    {
        $this->krynCore = $krynCore;
        $this->loadMessages();
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
        if (!$this->krynCore->getSystemConfig()->getLanguages() && $lang == 'en') {
            return true;
        } //default

        if ($this->krynCore->getSystemConfig()->getLanguages()) {
            $languages = explode(',', preg_replace('/[^a-zA-Z0-9]/', '', $this->krynCore->getSystemConfig()->getLanguages()));
            return array_search($lang, $languages) !== true;
        } else {
            return $lang == 'en';
        }
    }

    public function loadMessages($lang = 'en', $force = false)
    {
        if (!$this->isValidLanguage($lang)) {
            $lang = 'en';
        }

        if ($this->messages && isset($this->messages['__lang']) && $this->messages['__lang'] == $lang && $force == false) {
            return;
        }

        if (!$lang) {
            return;
        }

        $code = 'cacheLang_' . $lang;
        $this->messages =& $this->krynCore->getFastCache()->get($code);

        $md5 = '';
        $bundles = array();
        foreach ($this->krynCore->getKernel()->getBundles() as $bundleName => $bundle) {
            $path = $bundle->getPath();
            if ($path) {
                $path .= "Resources/translations/$lang.po";
                $md5 .= @filemtime($path);
                $bundles[] = $bundleName;
            }
        }

        $md5 = md5($md5);

        if ((!$this->messages || count($this->messages) == 0) || !isset($this->messages['__md5']) || $this->messages['__md5'] != $md5) {

            $this->messages = array('__md5' => $md5, '__plural' => $this->getPluralForm($lang), '__lang' => $lang);

            foreach ($bundles as $key) {
                $file = $this->krynCore->resolvePath("@$key/$lang.po", 'Resources/translations');
                $po = $this->getLanguage($file);
                $this->messages = array_merge($this->messages, $po['translations']);
            }
            $this->krynCore->getFastCache()->set($code, $this->messages);
        }

        include_once($this->getPluralPhpFunctionFile($lang));

        return $this->messages;
    }


    public function getPluralPhpFunctionFile($lang)
    {
        $fs = $this->krynCore->getCacheFileSystem();

        $file = 'core_gettext_plural_fn_' . $lang . '.php';
        if (!$fs->has($file)) {
            $pluralForm = $this->getPluralForm($lang, true);

            $code = "<?php

if (!function_exists('gettext_plural_fn_$lang')) {
    function gettext_plural_fn_$lang(\$n){
        return " . str_replace('n', '$n', $pluralForm) . ";
    }
}
";
            $fs->write($file, $code);
        }

        return $fs->getAdapter()->getRoot() . '/' . $file;
    }


    /**
     * @param $lang
     *
     * @return string Returns the public accessible file path
     */
    public function getPluralJsFunctionFile($lang)
    {
        $fs = $this->krynCore->getWebFileSystem();

        $file = 'cache/core_gettext_plural_fn_' . $lang . '.js';
        if (!$fs->has($file)) {
            $pluralForm = $this->getPluralForm($lang, true);

            $code = "function gettext_plural_fn_$lang(n){\n";
            $code .= "    return " . $pluralForm . ";\n";
            $code .= "}";
            $fs->write($file, $code);
        }

        return 'cache/core_gettext_plural_fn_' . $lang . '.js';
    }

    public function getLanguage($file)
    {
        $res = array('header' => array(), 'translations' => array(), 'file' => $file);
        if (!file_exists($file)) {
            return $res;
        }

        $lastPluralId = $lastId = $lastWasPlural = $inHeader = $nextIsThisContext = null;

        $fh = fopen($file, 'r');

        while (($buffer = fgets($fh)) !== false) {
            if (preg_match('/^msgctxt "(((\\\\.)|[^"])*)"/', $buffer, $match)) {
                $lastWasPlural = false;
                $nextIsThisContext = $match[1];
            }

            if (preg_match('/^msgid "(((\\\\.)|[^"])*)"/', $buffer, $match)) {
                $lastWasPlural = false;
                if ($match[1] == '') {
                    $inHeader = true;
                } else {
                    $inHeader = false;
                    $lastId = $match[1];
                    if ($nextIsThisContext) {
                        $lastId = $nextIsThisContext . "\004" . $lastId;
                        $nextIsThisContext = false;
                    }

                }
            }

            if (preg_match('/^msgstr "(((\\\\.)|[^"])*)"/', $buffer, $match)) {
                if ($inHeader == false) {
                    $lastWasPlural = false;
                    $res['translations'][static::evalString($lastId)] = static::evalString($match[1]);
                }
            }

            if (preg_match('/^msgid_plural "(((\\\\.)|[^"])*)"/', $buffer, $match)) {
                if ($inHeader == false) {
                    $lastWasPlural = true;
                    $res['plurals'][static::evalString($lastId)] = static::evalString($match[1]);
                }
            }

            if (preg_match('/^msgstr\[([0-9]+)\] "(((\\\\.)|[^"])*)"/', $buffer, $match)) {
                if ($inHeader == false) {
                    $lastPluralId = intval($match[1]);
                    $res['translations'][static::evalString($lastId)][$lastPluralId] = static::evalString($match[2]);
                }
            }

            if (preg_match('/^"(((\\\\.)|[^"])*)"/', $buffer, $match)) {
                if ($inHeader == true) {
                    $fp = strpos($match[1], ': ');
                    $res['header'][substr($match[1], 0, $fp)] = str_replace('\n', '', substr($match[1], $fp + 2));
                } else {
                    if (is_array($res['translations'][$lastId])) {
                        $res['translations'][static::evalString($lastId)][$lastPluralId] .= static::evalString($match[1]);
                    } else {
                        if ($lastWasPlural) {
                            $res['plurals'][static::evalString($lastId)] .= static::evalString($match[1]);
                        } else {
                            $res['translations'][static::evalString($lastId)] .= static::evalString($match[1]);
                        }
                    }
                }
            }

        }

        return $res;
    }

    public static function evalString($p)
    {
        return stripcslashes($p);
    }

    public function getPluralForm($lang, $onlyAlgorithm = false)
    {
        //csv based on (c) http://translate.sourceforge.net/wiki/l10n/pluralforms
        $file = $this->krynCore->resolvePath('@KrynCmsBundle/Resources/package/gettext-plural-forms.csv');
        if (!file_exists($file)) {
            return false;
        }

        $fh = fopen($file, 'r');
        if (!$fh) {
            return false;
        }
        $result = '';
        while (($buffer = fgetcsv($fh, 1000)) !== false) {
            if ($buffer[0] == $lang) {
                fclose($fh);

                $result = $buffer[2];
                break;
            }
        }

        if ($onlyAlgorithm) {
            $pos = strpos($result, 'plural=');
            return substr($result, $pos + 7);
        } else {
            return $result;
        }
    }

    public function t($id, $plural = null, $count = 0, $context = null)
    {
        $id = ($context == '') ? $id : $context . "\004" . $id;

        if (isset($this->messages[$id])) {
            if (is_array($this->messages[$id])) {

                if ($count) {
                    $plural = intval(@call_user_func('gettext_plural_fn_' . $this->messages['__lang'], $count));
                    if ($count && $this->messages[$id][$plural]) {
                        return str_replace('%d', $count, $this->messages[$id][$plural]);
                    } else {
                        return (($count === null || $count === false || $count === 1) ? $id : $plural);
                    }
                } else {
                    return $this->messages[$id][0];
                }
            } else {
                return $this->messages[$id];
            }
        } else {
            return $id;
        }
    }

    public function tc($context, $id, $plural = null, $count = 0)
    {
        return $this->t($id, $plural, $count, $context);
    }

}