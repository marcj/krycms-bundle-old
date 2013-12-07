<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Kryn\CmsBundle\Command;

use Kryn\CmsBundle\Propel\PropelHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Propel\Generator\Config\XmlToArrayConverter;
use Propel\Generator\Config\ArrayToPhpConverter;

class BuildCommand extends AbstractCommand
{
    const DEFAULT_INPUT_DIRECTORY   = '.';
    const DEFAULT_INPUT_FILE        = 'runtime-conf.xml';
    const DEFAULT_OUTPUT_DIRECTORY  = './generated-conf';
    const DEFAULT_OUTPUT_FILE       = 'config.php';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('kryncms:models:build')
            ->setDescription('Builds all propel models in kryn bundles.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $propelHelper = new PropelHelper($this->getKrynCore());

        echo $propelHelper->generateClasses();

        $propelHelper->cleanup();
    }
}
