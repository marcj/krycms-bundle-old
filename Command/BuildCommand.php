<?php

namespace Kryn\CmsBundle\Command;

use Kryn\CmsBundle\Propel\PropelHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends AbstractCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('kryncms:models:build')
            ->setDescription('Builds all ORM models and updates their backend schema if needed (e.g. SQL tables).')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $modelBuilder = $this->getKrynCore()->getModelBuilder();
        $modelBuilder->build();
    }
}
