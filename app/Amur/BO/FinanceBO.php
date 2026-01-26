<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\MortgageTable;
use DateTime;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class FinanceBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    /*public function nsfToQb($request) {
        if (!$request->hasFile('file')) {
            $this->logger->error('FinanceBO->nsfToQb - File is empty');
            return false;
        }

        $file = $request->file('file');
        $fileKey = md5(uniqid());

        $content = File::get($file);
        $lines = preg_split('/\n|\r|\n\r/', $content);

        $parsed = $this->parseNsfLines($lines);

        $papFileBO = new PapFileBO($this->logger, $this->db);
        $header = $papFileBO->getQBHeader();
        $endTransaction = $papFileBO->getQBEndTrans();

        $fileKey = md5(uniqid());
        Storage::append('tmp/' . $fileKey, $header);

        $transactions = array();
        foreach($parsed['records'] as $key => $records) {
            foreach($records as $record) {
                if (!isset($transactions[$key])) {
                    $transactions[$key]['records'] = array();
                    $transactions[$key]['total'] = 0;
                }

                $companyId = $record['micCompany'] == 'RQL' ? 183 : null;
                $transaction = $this->getQBTrans($record['date'], $record['amount'], $record['clientName'], $record['mortgageCode'], $companyId);

                $transactions[$key]['records'][] = $transaction;
                $transactions[$key]['total'] += $record['amount'];
            }
        }

        foreach($transactions as $key => $value) {
            $startTransaction = $this->getQBStartTrans($parsed['reportDate'], $value['total'] * -1, 'NSF ' . $parsed['reportDate']->format('M/d-Y'));
            Storage::append('tmp/' . $fileKey, $startTransaction);

            foreach($value['records'] as $transaction) {
                Storage::append('tmp/' . $fileKey, $transaction);
            }

            Storage::append('tmp/' . $fileKey, $endTransaction);
        }

        return base64_encode(Storage::get('tmp/' . $fileKey));
    }*/

    public function nsfToNetsuite($request) {
        if(!$request->hasFile('file')) {
            $this->logger->error('FinanceBO->nsfToNetsuite - File is empty');
            return false;
        }

        $file = $request->file('file');
        $content = File::get($file);
        $lines = preg_split('/\n|\r|\n\r/', $content);

        $parsed = $this->parseNsfLines($lines);

        $fileKey = md5(uniqid());

        // Create CSV header
        $csv = "Transaction Type,Account Bank,File No,Check #,Subsidiary,tranDate,postingPeriodRef,currencyRef,exchangeRate,memo,line memo,itemLine_quantity,Payment Amount,GL account\n";

        // Process each record
        foreach($parsed['records'] as $records) {
            foreach($records as $key => $record) {
                $accountBank = '';
                $subsidiary = '';
                $tranId = '';
                $prefix = '';

                // If it's an ABL mortgage, use province-based account
                if(strpos($record['mortgageCode'], 'ABL') === 0 && $record['province']) {
                    $subsidiary = '17';
                    $prefix = 'ABL';
                    $glAccount = '23650';

                    if($record['province'] === 'ON') {
                        $accountBank = '10520';
                    } elseif(in_array($record['province'], ['AB', 'BC'])) {
                        $accountBank = '10510';
                    }
                } else {
                    // Use existing company-based logic
                    switch ($record['micCompany']) {
                        case 'BSF':
                            $accountBank = '10750';
                            $glAccount = '12010';
                            $subsidiary = '12';
                            $prefix = 'ACHYF';
                            break;
                        case 'RQL':
                            $accountBank = '10660';
                            $glAccount = '12010';
                            $subsidiary = '10';
                            $prefix = 'ACIF';
                            break;
                        case 'RMC':
                            $accountBank = '10660';
                            $glAccount = '12010';
                            $subsidiary = '10';
                            $prefix = 'ACIF';
                            break;
                        case 'MII':
                            $accountBank = '10700';
                            $glAccount = '12010';
                            $subsidiary = '11';
                            $prefix = 'ACCIF';
                            break;
                    }
                }

                $tranId = $prefix . '-NSF-' . $record['fileNumber'] . str_pad($record['reportDate']->format('d'), 2, '0', STR_PAD_LEFT);

                // Properly escape the client name if it contains a comma
                $clientName = $record['clientName'];
                $clientName = str_replace('"', '""', $clientName);
                $clientName = '"' . $clientName . '|' . $record['mortgageCode'] . '"';

                $memo = 'NSF #' . $record['fileNumber'];

                // Create CSV row
                $row = [
                    'NSF', // Transaction Type
                    $accountBank,
                    $record['mortgageCode'],
                    $tranId,
                    $subsidiary,
                    $record['reportDate']->format('m/d/Y'),
                    $record['reportDate']->format('M Y'),
                    'CAD',
                    '1',
                    $memo,
                    $clientName,
                    '1',
                    $record['amount'],
                    $glAccount
                ];

                $csv .= implode(',', $row) . "\n";
            }
        }

        Storage::put('tmp/' . $fileKey, $csv);
        return base64_encode(Storage::get('tmp/' . $fileKey));
    }

    public function parseNsfLines($lines) {
        $records = array();
        $reportDate = new DateTime();
        $fileNumber = '';
        $key = 0;
        $papFileBO = new PapFileBO($this->logger, $this->db);

        foreach($lines as $line) {
            if(substr(trim($line), 0, 16) == 'SETTLEMENT DATE:') {
                $reportDate = DateTime::createFromFormat('M d,Y', trim(str_replace('SETTLEMENT DATE:', '', $line)));
            }

            if(substr(trim($line), 0, 28) == '*** TOTAL ITEMS FOR FILE ***') {
                $key++;
            }

            if(substr(trim($line), 0, 20) == 'FILE CREATION NO.  :') {
                $fileNumber = trim(str_replace('FILE CREATION NO.  :', '', $line));
            }

            $mic = substr($line, 74, 3);
            $mortgageCode = trim(substr($line, 74, 8));

            // Check for either MIC companies or ABL mortgage codes
            if(in_array($mic, ['RMC', 'RQL', 'MII', 'BSF']) || strpos($mortgageCode, 'ABL') === 0) {
                $clientName = trim(substr($line, 43, 31));
                $amount = trim(substr($line, 98, 11));
                $amount = str_replace('$', '', $amount);
                $amount = str_replace(',', '', $amount);

                $month = trim(substr($line, 10, 3));
                $day = str_pad(trim(substr($line, 14, 2)), 2, '0', STR_PAD_LEFT);

                if ($month == 'DEC' && (new DateTime())->format('m') == 1) {
                    $date = DateTime::createFromFormat('MdY', $month . $day . ((new DateTime())->format('Y')) - 1);
                } else {
                    $date = DateTime::createFromFormat('MdY', $month . $day . ((new DateTime())->format('Y')));
                }

                $record = [
                    'micCompany' => $mic,
                    'clientName' => $clientName,
                    'mortgageCode' => $mortgageCode,
                    'amount' => $amount,
                    'date' => $date,
                    'accountBank' => null,  // Will be set in nsfToNetsuite based on micCompany or province
                    'province' => null,
                    'mortgage_id' => null,
                    'payment_id' => null,
                    'fileNumber' => $fileNumber,
                    'reportDate' => clone $reportDate,
                ];

                // Get mortgage info based on type
                if(strpos($mortgageCode, 'ABL') === 0) {
                    $mortgageTable = MortgageTable::where('mortgage_code', $mortgageCode)->first();

                    // Get province using PapFileBO's getMainPropertyProvince public function
                    $papFileBO = new PapFileBO($this->logger, $this->db);
                    $record['province'] = $papFileBO->getMainPropertyProvince($mortgageTable->mortgage_id);
                }

                $records[$key][] = $record;
            }
        }

        return ['records' => $records];
    }

    public function getQBTrans(DateTime $date, $amount, $lastName, $mortgageCode, $companyId) {

        if ($companyId == 183) {
            $record = "SPL\t\tCHEQUE\t[tag_date]\t1403 " . hex2bin('b7') . " Mortgage on Mortgage QC\t\t\t[tag_amount]\t\t\"[tag_last_name] [tag_mortgage_code]\"\t \t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r";
        } else {
            $record = "SPL\t\tCHEQUE\t[tag_date]\t1401 " . hex2bin('b7') . " Mortgage Receivable\t\t\t[tag_amount]\t\t\"[tag_last_name] [tag_mortgage_code]\"\t \t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r";
        }
        $record = str_replace('[tag_date]', $date->format('n/j/Y'), $record);
        $record = str_replace('[tag_amount]', $amount, $record);
        $record = str_replace('[tag_last_name]', $lastName, $record);
        $record = str_replace('[tag_mortgage_code]', $mortgageCode, $record);

        return $record;
    }

    public function getQBStartTrans(DateTime $date, $amount, $fileNumber) {

        $record = "TRNS\t\tCHEQUE\t[tag_date]\t1008-01 " . hex2bin('b7') . " BMO General Account\tBMO - NSF's\t\t[tag_amount]\t\t[tag_file_id]\t\t \t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r";

        $record = str_replace('[tag_date]', $date->format('n/j/Y'), $record);
        $record = str_replace('[tag_amount]', $amount, $record);
        $record = str_replace('[tag_file_id]', $fileNumber, $record);

        return $record;
    }
}
