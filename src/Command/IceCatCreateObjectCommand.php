<?php

namespace IceCatBundle\Command;

use IceCatBundle\Services\CreateObjectService;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IceCatCreateObjectCommand extends AbstractCommand
{
    private $importObject;

    public function __construct(CreateObjectService $ob)
    {
        $this->importObject = $ob;
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('icecat:create-object')->setDescription('IMPORT DATA FROM ICECAT');
        $this->addArgument('userId', InputArgument::REQUIRED, 'Please enter user id');
        $this->addArgument('jobId', InputArgument::REQUIRED, 'Please enter job id');
        $this->addArgument('ignoreVersion', InputArgument::OPTIONAL, 'Enter 1 to ignore ');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $userId = $input->getArgument('userId');
        $jobId = $input->getArgument('jobId');
        $this->importObject->createObject($userId, $jobId);
        $this->writeInfo('Import Completed');
        return 0;
    }
}
