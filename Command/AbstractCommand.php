<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Kryn\CmsBundle\Command;

use Propel\Generator\Config\GeneratorConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Propel\Generator\Exception\RuntimeException;

abstract class AbstractCommand extends Command
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
        return $this->getApplication()->getKernel()->getContainer()->get('kryn.cms');
    }
}
