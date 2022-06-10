<?php

namespace IceCatBundle\Services;

use Symfony\Component\HttpFoundation\RequestStack;

class DataService extends InfoService
{
    public function __construct(RequestStack $requestStack)
    {
        parent::__construct($requestStack);
    }

    public function getFoundProductCountoShowInGrid()
    {
        $jobId = $this->getRunningJobId();
        if (!$jobId) {
            return ['jobid' => false, 'total' => '0'];
        }

        $fetchedRecords = $this->getTotalFetchedProducts($jobId);
        if ($fetchedRecords) {
            $result = ['jobid' => true, 'total' => $fetchedRecords['fetched_records']];
        } else {
            $result = ['jobid' => true, 'total' => '0'];
        }

        return $result;
    }

    public function getProductThatareNotFound()
    {
        $jobId = $this->getRunningJobId();
        if (!$jobId) {
            return ['jobid' => false, 'product' => '0'];
        }

        $notFoundsProduct = $this->getInfoForUnfoundProducts($jobId);

        if ($notFoundsProduct) {
            $result = ['jobid' => true, 'product' => $notFoundsProduct];
        } else {
            $result = ['jobid' => true, 'product' => '0'];
        }

        return $result;
    }

    public function getDataForFetchingProgressbar()
    {
        $jobId = $this->getRunningJobId();
        if (!$jobId) {
            return [];
        }
        $pbData = $this->getFetchingProcess($jobId);
        if (empty($pbData)) {
            return [];
        }

        $pbData = current($pbData);

        $totalSteps = $pbData['total_fetch_records'];
        $blankRecords = $pbData['fetched_blank_records'] * $pbData['total_languages'];
        if (!empty($blankRecords)) {
            $totalSteps = $totalSteps - $blankRecords;
        }
        //        $currentSteps = $previousCounter + 1;
        $currentSteps = $pbData['fetched_records'];
        $item = [
            'progressPercentage' => $this->calculateProgressPercentage($currentSteps, $totalSteps),
            'currentStep' => $currentSteps,
            'totalSteps' => $totalSteps,
            'id' => $pbData['jobid'],
        ];

        return [$item];
    }

    public function getDataForCreationProgressbar()
    {
        $jobId = $this->getRunningJobId();
        if (!$jobId) {
            return [];
        }
        $pbData = $this->getObjectCreationProcess($jobId);
        if (empty($pbData)) {
            return [];
        }

        $pbData = current($pbData);

        $totalSteps = $pbData['total_records'];

        $blankRecords = $pbData['fetched_blank_records'];
        //        if (!empty($blankRecords)) {
        //            $totalSteps = $totalSteps - $blankRecords;
        //        }
        $currentSteps = $pbData['processed_records'];
        $item = [
            'progressPercentage' => $this->calculateProgressPercentage($currentSteps, $totalSteps),
            'currentStep' => $currentSteps,
            'totalSteps' => $totalSteps,
            'id' => $pbData['jobid'],
        ];

        return [$item];
    }

    protected function calculateProgressPercentage($steps, $totalSteps)
    {
        if ($steps === 0 || $totalSteps === 0) {
            return 0;
        }

        return ($steps * 100) / $totalSteps;
    }

    public function getDataForImportGrid($start, $limit, $page)
    {
        $jobId = $this->getRunningJobId();
        if (!$jobId) {
            return ['data' => [], 'total' => 0];
        }
        $result = $this->getFetchingProcess($jobId);
        if (empty($result)) {
            return ['data' => [], 'total' => 0];
        }
        $jobId = $result[0]['jobid'];
        $totalRecords = $result[0]['fetched_records'];

        $gtins = $this->getNotToImportGtinsFromSession($page);

        if ($gtins) {
            $gtins = '("' . implode('","', explode(',', $gtins)) . '")';
        } else {
            $gtins = '("dummyText")';
        }

        $sql = 'SELECT if(gtin in ' . $gtins . ', 1, 0) as sel, language, gtin, original_gtin ,product_name, is_product_found, created_at as fetching_date FROM '
            .  self::DATA_IMPORT_TABLE
            . ' WHERE job_id="' . $jobId . '" AND is_product_found=1 '
            . ' LIMIT ' . $start . ',' . $limit;
        $data = $this->db->fetchAll($sql);

        $finalData = [];
        foreach ($data as $d) {
            $d['language'] = \Locale::getDisplayLanguage($d['language']);
            $finalData[] = $d;
        }

        $sql = 'SELECT count(gtin) as count  FROM '
            .  self::DATA_IMPORT_TABLE
            . ' WHERE job_id="' . $jobId . '" AND is_product_found=1 ';

        $count = $this->db->fetchRow($sql);
        //        return ['data' => $data, 'total' => $totalRecords];
        return ['data' => $finalData, 'total' => $count['count']];
    }

    public function commitNotToImportRecords($jobId)
    {
        $gtinsBatch = 200;
        $gtins = $this->getNotToImportGtinsFromSession('all');
        $this->emptySelectedGtinsFromSession();

        $sql = 'UPDATE ' . self::DATA_IMPORT_TABLE . " SET to_be_created=0 WHERE job_id='" . $jobId . "'";
        $this->db->exec($sql);

        if (empty($gtins)) {
            return ['error' => 'NoData'];
        }

        $gtins = explode(',', $gtins);

        $totalGtins = count($gtins);
        $processedCounter = 0;
        for ($start = 0; $start < $totalGtins / $gtinsBatch; $start++) {
            $values = array_slice($gtins, $processedCounter, $gtinsBatch);
            $values = '"' . implode('","', $values) . '"';
            $sql = 'UPDATE ' . self::DATA_IMPORT_TABLE . ' SET to_be_created=1 WHERE gtin in (' . $values .   ") and job_id='" . $jobId . "'";
            $this->db->exec($sql);
            $processedCounter += $gtinsBatch;
            if ($processedCounter > $totalGtins) {
                break;
            }
        }
    }

    public function terminateJob()
    {
    }
}
