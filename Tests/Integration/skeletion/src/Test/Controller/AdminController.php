<?php

namespace Test\Controller;

use RestService\Server;
use Core\Kryn;

class AdminController extends Server {

    public function run($pEntryPoint){

        $this->addGetRoute('session', 'getSession');

        return parent::run();

    }

    public function getSession(){

        return Kryn::getAdminClient()->getSession()->getId().
            '-'.Kryn::getAdminClient()->getSession()->getTime().'-'.(Kryn::getAdminClient()->getSession()->getUserId()+0);
    }
}

?>