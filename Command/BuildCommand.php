<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Kryn\CmsBundle\Command;

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
            ->addOption('connection', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Connection to use. Example: default, bookstore')
            ->setDescription('Builds all propel models in kryn bundles.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $bundles = $this->getApplication()->getKernel()->getBundles();

        foreach ($bundles as $bundleName => $bundle) {
            $dir = $bundle->getPath() . '/Resources/config/';
            if (is_dir($dir)) {
                $in = new ArrayInput(array(
                    'command'       => 'propel:model:build',
                    'bundle'        => $bundleName,
                    '--connection'  => $input->getOption('connection'),
                    '--verbose'     => $input->getOption('verbose'),
                ));
                $cmd = $this->getApplication()->find('propel:model:build');
                $cmd->run($in, $output);
            }
        }

        return;

        $this->platform = $input->getOption('platform');
        $this->verbose = $input->getOption('verbose');

        $files = $this->getAllSchemas();

        $bundles = $this->getApplication()->getKernel()->getBundles();
        foreach ($bundles as $bundle) {

        }
        var_dump($bundles);

        $output->writeln('Hi');
        return;
        $inputFilePath = $input->getOption('input-dir') . DIRECTORY_SEPARATOR . $input->getOption('input-file');
        if (!file_exists($inputFilePath)) {
            throw new \RuntimeException(sprintf('Unable to find the "%s" configuration file', $inputFilePath));
        }

        $this->createDirectory($input->getOption('output-dir'));

        $outputFilePath = $input->getOption('output-dir') . DIRECTORY_SEPARATOR .$input->getOption('output-file');
        if (!is_writable(dirname($outputFilePath))) {
            throw new \RuntimeException(sprintf('Unable to write the "%s" output file', $outputFilePath));
        }

        $stringConf = file_get_contents($inputFilePath);
        $arrayConf  = XmlToArrayConverter::convert($stringConf);
        $phpConf    = ArrayToPhpConverter::convert($arrayConf);
        $phpConf    = "<?php
" . $phpConf;

        if (file_exists($outputFilePath)) {
            $currentContent = file_get_contents($outputFilePath);
            if ($currentContent == $phpConf) {
                $output->writeln(sprintf('No change required in the current configuration file <info>"%s"</info>.', $outputFilePath));

            } else {
                file_put_contents($outputFilePath, $phpConf);
                $output->writeln(sprintf('Successfully updated PHP configuration in file <info>"%s"</info>.', $outputFilePath));
            }
        } else {
            file_put_contents($outputFilePath, $phpConf);
            $output->writeln(sprintf('Successfully wrote PHP configuration in file <info>"%s"</info>.', $outputFilePath));
        }
    }
}
