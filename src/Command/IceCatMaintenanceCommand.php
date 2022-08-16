<?php

namespace IceCatBundle\Command;

use IceCatBundle\Services\ImportService;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IceCatMaintenanceCommand extends AbstractCommand
{
    private $importObject;

    public function __construct(ImportService $ob)
    {
        $this->importObject = $ob;
        parent::__construct();
    }

    //sets name and description
    public function configure()
    {
        $this->setName('icecat:import')->setDescription('IMPORT DATA FROM ICECAT')
            ->addArgument('jobId', InputArgument::REQUIRED);
    }

    // Calls a method of IceCatBundle\Services\ImportService that import
    // data from icecat and return response accordingly
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $jobId = $input->getArgument('jobId');
        $result = $this->importObject->importData($jobId);
        if ($result['status'] == 'success') {
            $this->writeInfo('Import Completed');

            return 0;
        } else {
            $this->writeInfo('Import Completed with error: ' . $result['message']);

            return 1;
        }
    }
}
