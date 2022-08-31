<?php

namespace IceCatBundle\Command;

use IceCatBundle\Services\IceCatMaintenanceService;
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

    public function configure()
    {
        $this->setName('icecat:refresh')
            ->setDescription('refresh ice cat product')
            ->addArgument('objId', InputArgument::REQUIRED)
            ->addArgument('langs', InputArgument::OPTIONAL);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $objId = $input->getArgument('objId');
        $langs = $input->getArgument('langs');
        if (empty($langs)) {
            $langs = 'en';
        }
        $this->service->refreshProduct($objId, $langs);
        $this->writeInfo('Import Completed');
        return 0;
    }
}
