<?php

namespace Kryn\CmsBundle\Model;

use Kryn\CmsBundle\Client\ClientAbstract;
use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Model\Base\User as BaseUser;

class User extends BaseUser
{

    /**
     * Converts $password in a hash and set it.
     * If no salt is already generated, this generates one.
     *
     * @param string $password plain password
     * @param Core $krynCore
     *
     */
    public function setPassword($password, Core $krynCore)
    {
        if (!$this->getPasswdSalt()) {
            $this->setPasswdSalt(ClientAbstract::getSalt());
        }

        $password = ClientAbstract::getHashedPassword($password, $this->getPasswdSalt(), $krynCore);

        $this->setPasswd($password);
    }
}
