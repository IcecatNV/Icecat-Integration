<?php

namespace IceCatBundle\Command;

use IceCatBundle\Services\IceCatMaintenanceService;
use IceCatBundle\Services\ImportService;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshProductCommand extends AbstractCommand
{
    private $service;

    public function __construct(IceCatMaintenanceService $service)
    {
        $this->service = $service;
        parent::__construct();
    }

    //sets name and description
    public function configure()
    {
        $this->setName('icecat:refresh')
            ->setDescription('refresh ice cat product')
            ->addArgument('objId', InputArgument::REQUIRED)
            ->addArgument('langs', InputArgument::OPTIONAL);
    }

    // Calls a method of IceCatBundle\Services\ImportService that import
    // data from icecat and return response accordingly
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $objId = $input->getArgument('objId');
        $langs = $input->getArgument('langs');
        if (empty($langs)) {
            $langs = 'en';
        }
        $this->service->refreshProduct($objId, $langs);
        $this->writeInfo('Import Completed');
//        if ($result['status'] == 'success') {
//
//            return 0;
//        } else {
//            $this->writeInfo('Import Completed with error: ' . $result['message']);
//
//            return 1;
//        }
    }
}
