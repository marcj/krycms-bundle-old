<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Kryn\CmsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class AbstractCommand extends ContainerAwareCommand
{
    const DEFAULT_INPUT_DIRECTORY   = '.';
    const DEFAULT_PLATFORM          = 'MysqlPlatform';

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
