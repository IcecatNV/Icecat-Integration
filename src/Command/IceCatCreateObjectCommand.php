<?php

namespace IceCatBundle\Command;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use IceCatBundle\Services\CreateObjectService;
use Symfony\Component\Console\Input\InputArgument;


class IceCatCreateObjectCommand extends AbstractCommand
{


    private $importObject;
    public function __construct(CreateObjectService $ob)
    {
        $this->importObject = $ob;
        parent::__construct();
    }

    //sets name and description
    public function configure()
    {
        $this->setName('icecat:create-object')->setDescription('IMPORT DATA FROM ICECAT');
        $this->addArgument('userId', InputArgument::REQUIRED, 'Please enter user id');
        $this->addArgument('jobId', InputArgument::REQUIRED, 'Please enter job id');
        $this->addArgument('ignoreVersion', InputArgument::OPTIONAL, 'Enter 1 to ignore ');
    }

    // Calls a method of IceCatBundle\Services\ImportService that import
    // data from icecat and return response accordingly
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $userId =  $input->getArgument('userId');
        $jobId =  $input->getArgument('jobId');
        $this->importObject->CreateObject($userId, $jobId);
        $this->writeInfo("Import Completed");
        return 0;
    }
}
