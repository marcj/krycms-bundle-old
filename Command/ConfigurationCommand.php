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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Propel\Generator\Config\XmlToArrayConverter;
use Propel\Generator\Config\ArrayToPhpConverter;

class ConfigurationCommand extends AbstractCommand
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
            ->setName('kryncms:configuration:database')
            ->setDescription('Builds all propel models in kryn bundles.')
            ->addArgument('type', InputArgument::REQUIRED, 'database type: mysql|pgsql|sqlite')
            ->addArgument('database-name', InputArgument::REQUIRED, 'database name')
            ->addArgument('username', InputArgument::REQUIRED, 'database login username')
            ->addArgument('pw', InputArgument::OPTIONAL, "use '' to define a empty password")
            ->addArgument('server', InputArgument::OPTIONAL, 'hostname or ip')
            ->addArgument('port', InputArgument::OPTIONAL)
            ->setHelp('
You can set with this command configuration values inside the app/config/config.kryn.xml file.

It overwrites only options that you provide.

')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $systemConfig = $this->getKrynCore()->getSystemConfig(false);

        $database = $systemConfig->getDatabase(true);

        $mainConnection = $database->getMainConnection();

        $mainConnection->setType($input->getArgument('type'));
        $mainConnection->setName($input->getArgument('database-name'));
        $mainConnection->setUsername($input->getArgument('username'));

        if (null !== $input->getArgument('pw')) {
            $mainConnection->setPassword($input->getArgument('pw'));
        }

        if (null !== $input->getArgument('server')) {
            $mainConnection->setServer($input->getArgument('server'));
        }

        if (null !== $input->getArgument('port')) {
            $mainConnection->setPort($input->getArgument('port'));
        }

        $path = realpath($this->getApplication()->getKernel()->getRootDir().'/..') . '/app/config/config.kryn.xml';
        $systemConfig->save($path);

        $cache = realpath($this->getApplication()->getKernel()->getRootDir().'/..') . '/app/config/config.kryn.xml.cache.php';
        @unlink($cache);

        $output->writeln(sprintf('File `%s` updated.', $path));
    }
}
