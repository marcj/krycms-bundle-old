<?php

namespace Kryn\CmsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class AbstractCommand extends ContainerAwareCommand
{
    protected $filesystem;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
    }

    /**
     * @return \Kryn\CmsBundle\Core
     */
    protected function getKrynCore()
    {
        return $this->getContainer()->get('kryn_cms');
    }
}
