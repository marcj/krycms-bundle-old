<?php

namespace Kryn\CmsBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class DemoDataCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('kryncms:install:demo')
            ->setDescription('Installs demo data.')
            ->addArgument('hostname', null, 'The hostname of the domain we should add. Example: 127.0.0.1')
            ->addArgument('path', null, 'The path of the domain we should add. Example: /kryn-1.0/ or just /')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        define('KRYN_MANAGER', true);
        $krynCore = $this->getKrynCore();

        $mainPackageManager = 'Kryn\CmsBundle\PackageManager';
        $packageManager = new $mainPackageManager();
        $packageManager->setDomain($input->getArgument('hostname'));
        $packageManager->setPath($input->getArgument('path'));
        $packageManager->setContainer($this->getContainer());
        $packageManager->installDemoData($krynCore);

        foreach ($krynCore->getKernel()->getBundles() as $bundle) {
            $class = $bundle->getNamespace() . '\\PackageManager';
            if ($class !== $mainPackageManager && class_exists($class)) {
                $packageManager = new $class;
                if ($packageManager instanceof ContainerAwareInterface) {
                    $packageManager->setContainer($this->getContainer());
                }
                if (method_exists($packageManager, 'installDemoData')) {
                    $packageManager->installDemoData($krynCore);
                }
            }
        }

        $this->getKrynCore()->invalidateCache('/');
    }
}
