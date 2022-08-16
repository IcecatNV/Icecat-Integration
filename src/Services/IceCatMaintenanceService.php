<?php


namespace IceCatBundle\Services;


class IceCatMaintenanceService
{
    public function importIceCatData()
    {

    }

    function processFile()
    {
        if ($filetype == 'csv') {
            $totalCsvCount = $this->getCsvTotalcount($file_open); //  * $this->totalLanguageCount;
            // updating fetching status
            $updateArray = [
                'fetching_status' => $this->status['FETCHING'], 'last_run_dateTime' => date('Y-m-d H:i:s'), 'total_fetch_records' => $totalCsvCount['totalRecords'] * $this->totalLanguageCount, 'fetched_blank_records' => $totalCsvCount['blankRecords'],
                'file_row_count' => $totalCsvCount['totalRecords']

            ];

            $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);

            if ($totalCsvCount < 1) {
                // now row found
                $result = ['status' => 'error', 'message' => 'No row found'];
                $updateArray = ['fetching_status' => $this->status['PARTIALLY_DONE'], 'fetching_error' => 'No row found', 'last_run_dateTime' => date('Y-m-d H:i:s')];
                $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);

                return $result;
            }

            $file_open = fopen($filepath, 'r');
            $headerArray = fgetcsv($file_open, 0, ',');
            $this->readerArray = array_change_key_case(array_flip(array_map('trim', $headerArray)), CASE_UPPER);
            $currentCsvCount = 1;
            while (($csvrow = fgetcsv($file_open, 0, ',')) !== false) {
                if (count(array_filter($csvrow)) >= 1) {
                    foreach ($this->languages as $language) {
                        $isJobAlive = $this->jobHandler->isLive($this->currentJObId);
                        if ($isJobAlive === false) {
                            $this->logMessage = 'JOB TERMINATED FROM FRONTEND:' . $this->currentJObId;
                            $this->logger->addLog('import', $this->logMessage, '', 'INFO');

                            return true;
                        }
                        $this->language = $language;
                        $result = $this->processRowData($csvrow, $fileName, $currentCsvCount);
                        $currentCsvCount++;
                    }
                }
            }
        } else {
            $spreadsheet = IOFactory::load($filepath);
            $sheets = $spreadsheet->getActiveSheet();
            $maxCell = $sheets->getHighestRowAndColumn();
            $excelDataArray = $sheets->rangeToArray('A1:' . $maxCell['column'] . $maxCell['row']);

            $headerArray = array_filter($excelDataArray[0]);
            $this->readerArray = array_change_key_case(array_flip(array_map('trim', $headerArray)), CASE_UPPER);
            unset($excelDataArray[0]);
            $currentXlsCount = 1;
            $totalXlsCount = count($excelDataArray); // * $this->totalLanguageCount;;

            $updateArray = [
                'status' => $this->status['FETCHING'], 'starting_dateTime' => date('Y-m-d H:i:s'), 'total_fetch_records' => $totalXlsCount * $this->totalLanguageCount,
                'file_row_count' => $totalXlsCount
            ];
            $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);

            foreach ($excelDataArray as $value) {
                // $rows[] =  $value;
                foreach ($this->languages as $language) {
                    $isJobAlive = $this->jobHandler->isLive($this->currentJObId);
                    if ($isJobAlive === false) {
                        $this->logMessage = 'JOB TERMINATED FROM FRONTEND:' . $this->currentJObId;
                        $this->logger->addLog('import', $this->logMessage, '', 'INFO');

                        return true;
                    }

                    $this->language = $language;
                    $result = $this->processRowData($value, $fileName, $currentXlsCount);
                    $currentXlsCount++;
                }
            }
        }
    }

    public function processRowData($singleRow, $filename, $counter)
    {
        $db = \Pimcore\Db::get();
        $this->gtin = -1;
        $this->currentProductIceCatId = '';
        $isProductFound = 0;
        $reason = 0;
        try {
            $url = $this->selectUrl($singleRow, $this->icecatUserName, $this->language);

            if ($url == -1) {
                $reason = self::REASON['INVALID_KEY'];

                $this->gtin = ($this->gtin == -1 || $this->gtin == '') ? 'ROW-' . $counter + 1 : $this->gtin;
                $insertQuery = 'INSERT INTO ' . self::IMPORTED_DATA_CONTAINER_TABLE . "(data_encoded,job_id,is_product_found,original_gtin,gtin
                ,data,pim_user_id,icecat_username,product_name,search_key,base_file, error, duplicate,language,reason) VALUES ('','$this->currentJObId' ,$isProductFound,'$this->gtin','$this->gtin ',
                '',$this->pimcoreUserId,'$this->icecatUserName','','', '$filename', 'URL not found to get product', (duplicate+1) , '$this->language','$reason')
                ON DUPLICATE KEY UPDATE data = VALUES(data) , updated_at = now() , duplicate=(duplicate+1)";
                try {
                    $result = $db->exec($insertQuery);
                    $this->insertCounter++;
                } catch (\Exception $ex) {
                }
                $updateArray = ['fetching_status' => $this->status['PARTIALLY_DONE'], 'fetched_records' => $counter, 'fetching_error' => 'URL not found to get product', 'last_run_dateTime' => date('Y-m-d H:i:s')];
                $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);

                // LOGGING
                $this->logMessage = 'STATUS: ' . $reason . 'ROW NUMBER :-' . $counter + 1  . 'FOR JOB ID :' . $this->currentJObId;
                $this->logger->addLog('import', $this->logMessage, '', 'NOTICE');

                return -1;
            }
            //Calling icecat api
            try {
                $response = $this->fetchIceCatData($url);
                $responseArray = json_decode($response, true);
            } catch (Exception $e) {
                // IN CASE OF INTERNET ACCESSIBLITY IS NOT AVAILABEL OR ICE CAT'S SERVER IS DOWN
                $response = '';
                $responseArray['COULD_NOT_RESOLVE_HOST'] = true;
            }

            $productName = '';
            if (array_key_exists('statusCode', $responseArray)) {
                if ($responseArray['statusCode'] == 4) {
                    $reason = self::REASON['PRODUCT_NOT_FOUND'];
                    $isProductFound = 0;
                } elseif ($responseArray['statusCode'] == 2) {
                    $reason = self::REASON['INVALID_LANGUAGE'];
                    $isProductFound = 0;
                }
            } elseif (array_key_exists('COULD_NOT_RESOLVE_HOST', $responseArray)) {
                $reason = self::REASON['COULD_NOT_RESOLVE_HOST'];
                $isProductFound = 0;
            } elseif (array_key_exists('msg', $responseArray) && $responseArray['msg'] == 'OK') {
                //Product Found
                $reason = '';
                $isProductFound = 1;
                $this->gtin = $responseArray['data']['GeneralInfo']['GTIN'][0];
                $this->currentProductIceCatId = $responseArray['data']['GeneralInfo']['IcecatId'];
                $this->productName = $productName = str_replace("'", "''", $responseArray['data']['GeneralInfo']['ProductName']);
            }

            // Proccessing String
            $processedResponse_temp = str_replace("'", "''", $response);
            $processedResponse = str_replace('\"', '\\\\"', $processedResponse_temp);
            $serializedSearchKey = serialize($this->searchKey);
            $fileName = $filename;
            $encodedResponse = base64_encode($response);

            $this->gtin = ($this->gtin == -1 || $this->gtin == '') ? 'ROW-' . $counter : $this->gtin;
            // if productId is not available then we will store row count in it
            $this->currentProductIceCatId = (empty($this->currentProductIceCatId)) ? 'ROW-' . $counter : $this->currentProductIceCatId;
            $insertQuery = 'INSERT INTO ' . self::IMPORTED_DATA_CONTAINER_TABLE . "(data_encoded,job_id,is_product_found,original_gtin,gtin
            ,data,pim_user_id,icecat_username,product_name,search_key,base_file, duplicate , language ,reason) VALUES ('$encodedResponse','$this->currentJObId' ,$isProductFound,'$this->gtin','$this->currentProductIceCatId',
            '$processedResponse',$this->pimcoreUserId,'$this->icecatUserName','$productName','$serializedSearchKey', '$fileName', (duplicate+1) ,  '$this->language','$reason')
            ON DUPLICATE KEY UPDATE data = VALUES(data) , updated_at = now(), duplicate=(duplicate+1), duplicate=(duplicate+1) ";
            $result = $db->exec($insertQuery);
            $this->insertCounter++;

            $updateArray = ['fetched_records' => $counter];
            $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);

            // LOGGING
            $this->logMessage = 'STATUS: ' . $reason . 'ROW NUMBER :-' . $counter + 1  . 'FOR JOB ID :' . $this->currentJObId;
            $this->logger->addLog('import', $this->logMessage, '', 'NOTICE');

            return $result;
        } catch (\Exception $e) {
            $isProductFound = 0;
            // LOGGING
            $this->logMessage = 'ERROR IN  PROCESSING ROW FOR JOB ID :' .  $this->currentJObId;
            $this->logger->addLog('import', $this->logMessage, [addslashes($e->getMessage()), $e->getTraceAsString()], 'ERROR');
            $this->gtin = ($this->gtin == -1 || $this->gtin == '') ? 'ROW-' . $counter : $this->gtin;
            $insertQuery = 'INSERT INTO ' . self::IMPORTED_DATA_CONTAINER_TABLE . "(data_encoded,job_id,is_product_found,original_gtin,gtin
            ,data,pim_user_id,icecat_username,product_name,search_key,base_file, error, duplicate) VALUES ('','$this->currentJObId' ,$isProductFound,'$this->gtin',
            '$this->gtin',
            '',$this->pimcoreUserId,'$this->icecatUserName', '$this->productName' ,'', '$filename', 'URL not found to get product', (duplicate+1))
            ON DUPLICATE KEY UPDATE data = VALUES(data) , updated_at = now() ";
            $this->insertCounter++;

            $result = $db->exec($insertQuery);
            $updateArray = ['fetching_status' => $this->status['PARTIALLY_DONE'], 'fetched_records' => $counter, 'fetching_error' => addslashes($e->getMessage()), 'last_run_dateTime' => date('Y-m-d H:i:s')];
            $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);
        }
    }
}
