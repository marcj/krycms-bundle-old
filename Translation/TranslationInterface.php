<?php

namespace Kryn\CmsBundle\Translation;

interface TranslationInterface {

    public function t($id, $plural = null, $count = 0, $context = null);
    public function tc($context, $id, $plural = null, $count = 0);
}