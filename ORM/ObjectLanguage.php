<?php

namespace Kryn\CmsBundle\ORM;


use Kryn\CmsBundle\Configuration\Condition;
use Propel\Runtime\ActiveQuery\ModelCriteria;

class ObjectLanguage extends Propel
{
    public $objectKey = 'kryncms/language';

    protected function modifyCondition(&$condition) {
        if (!$condition) {
            $condition = new Condition(null, $this->getKrynCore());
        }

        $languages = $this->getKrynCore()->getSystemConfig()->getLanguages();
        $languages = preg_replace('/\W+/', ',', $languages);
        $languages = explode(',', $languages);

        foreach ($languages as $lang) {
            $condition->addAnd(['code', '=', $lang]);
        }
    }

    public function getStm(ModelCriteria $query, Condition $condition = null)
    {
        $this->modifyCondition($condition);
        return parent::getStm($query, $condition);
    }

}