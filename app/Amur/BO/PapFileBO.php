<?php

namespace App\Amur\BO;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Amur\Utilities\Utils;
use App\Jobs\PapProcessPaymentFileQueue;
use App\Models\AlpineCompaniesTable;
use App\Models\Holiday;
use App\Models\MortgagePaymentsTable;
use App\Models\PapCredit;
use App\Models\PapFile;
use App\Models\PapFilePayment;
use DateInterval;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PapFileBO {

    private $logger;
    private $db;
    private $intervalDays = 1;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getFiles($type, DateTime $startDate, DateTime $endDate, $companyId) {
        $query = "select a.id, a.company_id, c.name company_name, a.originator_id, a.file_number,
                         a.file_name, a.reference_date, a.status, a.message, a.created_at,
                         concat(b.user_fname, ' ', b.user_lname) created_by
                    from pap_file a
                    join users_table b on a.created_by = b.user_id
                    join alpine_companies_table c on a.company_id = c.id
                   where a.deleted_at is null
                     and a.file_type = ?
                     and a.created_at >= ?
                     and a.created_at <= ?
                     and (a.company_id = ? or $companyId = 0)
                order by a.created_at desc";
        $res = $this->db->select($query, [$type, $startDate, $endDate, $companyId]);

        $files = array();
        $lastFileStatus = '';
        foreach ($res as $key => $value) {
            if ($lastFileStatus == '') {
                $lastFileStatus = $value->status;
            }

            $files[] = [
                'id' => $value->id,
                'companyId' => $value->company_id,
                'companyName' => $value->company_name,
                'originatorId' => $value->originator_id,
                'fileNumber' => $value->file_number,
                'fileName' => $value->file_name,
                'referenceDate' => new DateTime($value->reference_date),
                'status' => $value->status,
                'message' => $value->message,
                'createdAt' => new DateTime($value->created_at),
                'createdBy' => $value->created_by,
            ];
        }

        return ['files' => $files, 'lastFileStatus' => $lastFileStatus];
    }

    public function getPayments($companyId) {
        $config = $this->getConfigData($companyId);

        if (isset($config['companyIds'])) {
            $companies = implode(',', $config['companyIds']);
        } else {
            return array();
        }

        /**
         * 1. only payment types = Pre Payment will be returned or
         *    payment flagged as bank payment (PAP retakes)
         * 2. paid out files should not be returned due the balance = 0
         * 3. files in payout process should not be returned after admin or broker approval (it'll be suspended)
         * 4. for AB deals, only the parent card will be returned
         */
        $query = "select b.payment_id, a.mortgage_id, a.mortgage_code, b.processing_date, b.pmt_amt amount,
                         c.transit, c.account, d.code bank_code, c.validated_at,
                         c.payee_name
                    from mortgage_table a
                    join mortgage_payments_table b on a.mortgage_id = b.mortgage_id
               left join pap_bank c on c.mortgage_id = a.mortgage_id and c.deleted_at is null and c.validated_at is not null
               left join pap_institution d on c.institution_id = d.id
                   where a.company_id in ($companies)
                     and a.is_deleted = 'no'
                     and a.current_balance > 0
                     and b.pmt_amt > 0
                     and b.processing_date <= ?
                     and b.pap_file_payment_id is null
                     and (b.flag in ('Pre','Pre2','Pre3','Pre4') or b.is_bank_payment = 'yes')
                     and a.ab_loan <> 'c_inv'
                     and not exists (
                         select 'x' from payout_approval aa
                          where aa.mortgage_id = a.mortgage_id
                            and aa.deleted_at is null
                            and (aa.canceled_at is null or aa.status = 'R')
                            and (aa.admin_status = 'A' or aa.broker_status = 'A')
                     )
                order by a.mortgage_id, b.processing_date, c.validated_at desc";
        $res = $this->db->select($query, [$this->getBusinessDay(new DateTime(), $this->intervalDays)]);

        $payments = array();
        foreach ($res as $key => $value) {
            //this query can return duplicate payments due the bank account records
            //this condition will avoid it to be duplicate
            if (!isset($payments[$value->mortgage_id . '-' . $value->payment_id])) {

                $propertyProvince = null;
                //for AB deals, the payments should go to the province trusted bank account
                if (!is_null($config['province'])) {
                    $propertyProvince = $this->getMainPropertyProvince($value->mortgage_id);

                    if ($propertyProvince !== false && $config['province'] != $propertyProvince) {
                        continue;
                    }
                }

                $payments[$value->mortgage_id . '-' . $value->payment_id] = [
                    'paymentId' => $value->payment_id,
                    'mortgageId' => $value->mortgage_id,
                    'mortgageCode' => $value->mortgage_code,
                    'dueDate' => new DateTime($value->processing_date),
                    'amount' => $value->amount,
                    'bank' => $value->bank_code,
                    'transit' => $value->transit,
                    'account' => $value->account,
                    'clientName' => $this->formatClientName($value->payee_name),
                    'propertyProvince' => $propertyProvince
                ];
            }
        }

        return array_values($payments);
    }

    public function getMainPropertyProvince($mortgageId) {
        $sql = "select province
                  from mortgage_properties_table a
                  join property_table b on a.property_id = b.property_id
                 where a.mortgage_id = ?
              order by b.idx";
        $res = $this->db->select($sql, [$mortgageId]);

        if (count($res) > 0) {
            return $res[0]->province;
        } else {
            return false;
        }
    }

    public function getCredits($fileKey) {
        $awsBO = new AwsBO($this->logger);
        $file = $awsBO->getObject($fileKey);

        //save localy
        Storage::disk('local')->put('tmp/' . $fileKey . '.xlsx', $file);

        $filePath = Storage::disk('local')->getAdapter()->getPathPrefix() . 'tmp/' . $fileKey . '.xlsx';

        $inputFileType = IOFactory::identify($filePath);
        $reader = IOFactory::createReader($inputFileType);
        $spreadsheet = $reader->load($filePath);
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet()->toArray();

        $dueDate = $this->getBusinessDay(new DateTime(), 1);
        //$dueDate = new DateTime();

        $payments = array();
        foreach ($sheet as $key => $value) {
            $amount = Utils::toFloat($value[27]);

            if ($value[12] == 'EFT' && $amount > 0 && !empty($value[14])) {
                $payments[] = [
                    'paymentId' => $key,
                    'mortgageId' => null,
                    'mortgageCode' => substr(str_replace("\t", '', $value[7]), 0, 19),
                    'dueDate' => $dueDate,
                    'amount' => $amount,
                    'bank' => str_replace("\t", '', $value[14]),
                    'transit' => str_replace("\t", '', $value[13]),
                    'account' => str_replace("\t", '', $value[15]),
                    'clientName' => $this->formatClientName(str_replace("\t", '', $value[7])),
                    'propertyProvince' => null
                ];
            }
        }

        return $payments;
    }

    public function getSummary($companyId) {
        $payments = $this->getPayments($companyId);

        $totalPaymentsCount = 0;
        $totalPaymentsAmount = 0;
        foreach ($payments as $key => $payment) {
            $totalPaymentsCount++;
            $totalPaymentsAmount += $payment['amount'];
        }

        $alpineCompaniesTable = AlpineCompaniesTable::find($companyId);

        return [
            'totalPaymentsCount' => $totalPaymentsCount,
            'totalPaymentsAmount' => $totalPaymentsAmount,
            'companyName' => $alpineCompaniesTable->name ?? '',
            'referenceDate' => $this->getBusinessDay(new DateTime(), $this->intervalDays)
        ];
    }

    public function getNextFileNumber() {
        $papFile = PapFile::orderBy('id', 'desc')->first();
        if ($papFile) {
            if ($papFile->status == 'E') {
                $fileNumber = $papFile->file_number;
            } else {
                $fileNumber = $papFile->file_number + 1;
            }
        } else {
            $fileNumber = 1;
        }

        return $fileNumber;
    }

    public function requestFile($companyId) {
        $config = $this->getConfigData($companyId);
        $fileNumber = $this->getNextFileNumber();

        $papFile = new PapFile();
        $papFile->company_id = $companyId;
        $papFile->file_type = 'D';
        $papFile->originator_id = $config['debitOriginatorId'];
        $papFile->file_number = $fileNumber;
        $papFile->file_name = str_pad($fileNumber, 4, '0', 0) . '.txt';
        $papFile->reference_date = $this->getBusinessDay(new DateTime(), $this->intervalDays);
        $papFile->status = 'R';
        $papFile->save();

        //schedule a batch process to create the pap file
        PapProcessPaymentFileQueue::dispatch($papFile->id);

        return true;
    }

    public function getConfigData($companyId) {
        if ($companyId == 5) {
            return [
                'debitOriginatorId' =>  'RYANMT165F',
                'creditOriginatorId' => 'RYANM165FF',
                'destinationDataCentre' => '00120',
                'typeCodeDebit' => 371,
                'typeCodeCredit' => 260,
                'shortName' => 'AMUR CAPITAL IF',
                'longName' => 'AMUR CAPITAL INCOME FUND',
                'institutionForRet' => '1',
                'transitForRet' => '00040',
                'accountForRet' => '1439394',
                'companyIds' => [5, 183],
                'province' => null
            ];
        } elseif ($companyId == 16) {
            return [
                'debitOriginatorId' =>  'MANCHE202F',
                'creditOriginatorId' => 'MANCH202FF',
                'destinationDataCentre' => '00120',
                'typeCodeDebit' => 371,
                'typeCodeCredit' => 260,
                'shortName' => 'AMUR CAPITAL CF',
                'longName' => 'AMUR CAPITAL CON INCOME FUND',
                'institutionForRet' => '1',
                'transitForRet' => '00040',
                'accountForRet' => '1597202',
                'companyIds' => [16],
                'province' => null
            ];
        } elseif ($companyId == 182) {
            return [
                'debitOriginatorId' =>  'BLUE140DRF',
                'creditOriginatorId' => 'BLUE140CRF',
                'destinationDataCentre' => '00120',
                'typeCodeDebit' => 371,
                'typeCodeCredit' => 260,
                'shortName' => 'AMUR CAPITAL HF',
                'longName' => 'AMUR CAPITAL HIGH YIELD FUND',
                'institutionForRet' => '1',
                'transitForRet' => '00040',
                'accountForRet' => '1578140',
                'companyIds' => [182],
                'province' => null
            ];
        } elseif ($companyId == 1970) {
            //BC
            return [
                'debitOriginatorId' =>  'AFGBCTRTDR',
                'creditOriginatorId' => 'AFGBCTRTCR',
                'destinationDataCentre' => '00120',
                'typeCodeDebit' => 371,
                'typeCodeCredit' => 260,
                'shortName' => 'AMUR FINANCIAL',
                'longName' => 'AMUR FINANCIAL GROUP INC',
                'institutionForRet' => '1',
                'transitForRet' => '00040',
                'accountForRet' => '1568807',
                'companyIds' => [1970],
                'province' => 'BC'
            ];
        } elseif ($companyId == 1971) {
            //AB
            return [
                'debitOriginatorId' =>  'AFGBCTRTDR',
                'creditOriginatorId' => 'AFGBCTRTCR',
                'destinationDataCentre' => '00120',
                'typeCodeDebit' => 371,
                'typeCodeCredit' => 260,
                'shortName' => 'AMUR FINANCIAL',
                'longName' => 'AMUR FINANCIAL GROUP INC',
                'institutionForRet' => '1',
                'transitForRet' => '00040',
                'accountForRet' => '1568807',
                'companyIds' => [1970],
                'province' => 'AB'
            ];
        } elseif ($companyId == 1972) {
            //ON
            return [
                'debitOriginatorId' =>  'ABL10000DR',
                'creditOriginatorId' => 'ABL10000CR',
                'destinationDataCentre' => '00120',
                'typeCodeDebit' => 371,
                'typeCodeCredit' => 260,
                'shortName' => 'AMUR FINANCIAL',
                'longName' => 'AMUR FINANCIAL GROUP INC',
                'institutionForRet' => '1',
                'transitForRet' => '00022',
                'accountForRet' => '1586602',
                'companyIds' => [1970],
                'province' => 'ON'
            ];
        }

        return array();
    }

    public function processPaymentFile($papFileId) {
        $papFile = PapFile::find($papFileId);
        if (!$papFile) {
            $this->logger->error('PapFileBO->processPaymentFile - Pap file does not exist', [$papFileId]);
        }

        //user will know the processing has started
        $papFile->status = 'P';
        $papFile->save();

        $this->db->beginTransaction();
        try {
            $config = $this->getConfigData($papFile->company_id);
            $destinationDataCentre = $config['destinationDataCentre'];
            $shortName = $config['shortName'];
            $longName = $config['longName'];
            $institutionForRet = $config['institutionForRet'];
            $transitForRet = $config['transitForRet'];
            $accountForRet = $config['accountForRet'];

            if ($papFile->file_type == 'D') {
                $payments = $this->getPayments($papFile->company_id);
                $typeCode = $config['typeCodeDebit'];
            } else {
                $payments = $this->getCredits($papFile->original_file_key);
                $typeCode = $config['typeCodeCredit'];
            }

            $res = $this->validatePayments($payments, $papFile->file_type);

            if ($res !== true) {
                $this->db->rollback();

                $papFile->status = 'E';
                $papFile->message = $res;
                $papFile->save();

                $this->db->commit();
                return false;
            }

            //A.2 Records - Credit
            //A.3 Records - Debit
            $records = array();
            $recordNumber = 2;
            $positionNumber = 1;
            $totalPaymentsCount = 0;
            $totalPaymentsAmount = 0;
            foreach($payments as $payment) {
                if ($positionNumber > 6) {
                    $recordNumber++;
                    $positionNumber = 1;
                }

                if (!isset($records[$recordNumber])) {
                    $records[$recordNumber] = $papFile->file_type . str_pad($recordNumber, 9, '0', 0) . $papFile->originator_id . str_pad($papFile->file_number, 4, '0', 0);
                }

                $records[$recordNumber] .= $this->getDebitCreditLayout(
                    $papFile->file_type,
                    $typeCode,
                    $shortName,
                    $longName,
                    $institutionForRet,
                    $transitForRet,
                    $accountForRet,
                    $payment
                );

                if (!$this->updatePapFilePayment($papFile->id, $recordNumber, $positionNumber, $payment, $papFile->file_type)) {
                    throw new Exception('Could not insert pap_file_payment [' . $payment['mortgageId'] . ',' . $payment['paymentId'] . ']');
                }

                $positionNumber++;

                $totalPaymentsCount++;
                $totalPaymentsAmount += $payment['amount'];
            }

            $positionNumber--;
            if ($positionNumber < 6) {
                //to complete 1464 bytes on the last record
                $records[$recordNumber] .= str_pad(' ', 240 * (6 - $positionNumber), ' ', 0);
            }

            if (strlen($records[$recordNumber]) !== 1464) {
                $this->logger->error('PapFileBO->processPaymentFile line length exceeded', [
                    'fileId' => $papFileId,
                    'fileSize' => strlen($records[$recordNumber]),
                    'record' => $records[$recordNumber]
                ]);
                $this->db->rollback();

                $papFile->status = 'E';
                $papFile->message = 'File length exceeded - Contact IT';
                $papFile->save();

                $this->db->commit();
                return false;
            }

            //A.1 Header
            $header = $this->getHeader($papFile->originator_id, $papFile->file_number, $destinationDataCentre);

            //A.4 Trailer
            $trailer = $this->getTrailer($papFile->file_type, $recordNumber, $papFile->originator_id, $papFile->file_number, $totalPaymentsCount, $totalPaymentsAmount);

            //save file
            $fileKey = md5(uniqid());
            Storage::append('tmp/' . $fileKey, $header);
            foreach($records as $value) {
                Storage::append('tmp/' . $fileKey, $value);
            }
            Storage::append('tmp/' . $fileKey, $trailer);
            Storage::append('tmp/' . $fileKey, ''); //empty line

            //send file to S3
            $awsBO = new AwsBO($this->logger);
            if ($awsBO->uploadFile($fileKey, Storage::disk('local')->path('tmp/' . $fileKey)) === false) {
                throw new Exception('Error uploading file to AWS [' . $papFile->id . ',' . $fileKey . ']');
            }
        } catch (\Throwable $e) {
            $this->logger->error('PapFileBO->processPaymentFile', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();

            $papFile->status = 'E';
            $papFile->message = 'General error - Contact IT!';
            $papFile->save();

            $this->db->commit();
            return false;
        }

        //update file status
        $papFile->status = 'C';
        $papFile->file_key = $fileKey;
        $papFile->total_payment_count = $totalPaymentsCount;
        $papFile->total_payment_amount = $totalPaymentsAmount;
        $papFile->save();

        $this->db->commit();
    }

    public function getDebitCreditLayout($fileType, $typeCode, $shortName, $longName, $institutionForRet, $transitForRet, $accountForRet, $payment) {
        $record = '';
        $record .= $typeCode;
        $record .= str_pad($payment['amount'] * 100, 10, '0', 0);
        $record .= str_pad($payment['dueDate']->format('y') . str_pad($payment['dueDate']->format('z') + 1, 3, '0', 0), 6, '0', 0); //date
        $record .= '0' . str_pad($payment['bank'], 3, '0', 0) . str_pad($payment['transit'], 5, '0', 0); //institution
        $record .= str_pad($payment['account'], 12, ' ', 1); //account

        $record .= str_pad('0', 25, '0', 0); //filler

        $record .= str_pad($shortName, 15, ' ', 1); //orig short name
        $record .= str_pad($payment['clientName'], 30, ' ', 1); //client name
        $record .= str_pad($longName, 30, ' ', 1); //orig long name

        $record .= str_pad(' ', 10, ' ', 0); //filler

        $record .= str_pad($payment['mortgageCode'], 19, ' ', 1); //cross reference
        $record .= '0' . str_pad($institutionForRet, 3, '0', 0) . str_pad($transitForRet, 5, '0', 0); //institution for returns
        $record .= str_pad($accountForRet, 12, ' ', 1); //account for returns

        if ($fileType == 'D') {
            $record .= str_pad('0', 15, '0', 0); //filler
            $record .= str_pad('0', 18, '0', 0); //filler
            $record .= str_pad(' ', 6, ' ', 0); //filler
        } else {
            $record .= str_pad(' ', 39, ' ', 0); //filler
        }
        $record .= str_pad('0', 11, '0', 0); //filler

        return $record;
    }

    public function getHeader($originatorId, $fileNumber, $destinationDataCentre) {
        $now = new DateTime();

        $record = 'A';
        $record .= '000000001'; //record count
        $record .= $originatorId;
        $record .= str_pad($fileNumber, 4, '0', 0); //file #
        $record .= str_pad($now->format('y') . str_pad($now->format('z') + 1, 3, '0', 0), 6, '0', 0); //date
        $record .= $destinationDataCentre;
        $record .= str_pad(' ', 20, ' ', 0); //filler
        $record .= 'CAD';
        $record .= str_pad(' ', 1406, ' ', 0); //filler

        return $record;
    }

    public function getTrailer($fileType, $recordNumber, $originatorId, $fileNumber, $totalPaymentsCount, $totalPaymentsAmount) {
        $record = 'Z';
        $record .= str_pad($recordNumber + 1, 9, '0', 0); //record count
        $record .= $originatorId;
        $record .= str_pad($fileNumber, 4, '0', 0); //file #

        if ($fileType == 'D') {
            $record .= str_pad($totalPaymentsAmount * 100, 14, '0', 0);
            $record .= str_pad($totalPaymentsCount, 8, '0', 0);
            $record .= str_pad('0', 14, '0', 0);
            $record .= str_pad('0', 8, '0', 0);
        } else {
            $record .= str_pad('0', 14, '0', 0);
            $record .= str_pad('0', 8, '0', 0);
            $record .= str_pad($totalPaymentsAmount * 100, 14, '0', 0);
            $record .= str_pad($totalPaymentsCount, 8, '0', 0);
        }

        $record .= str_pad(' ', 1396, ' ', 0); //filler

        return $record;
    }

    public function formatClientName($clientName) {
        $clientName = trim($clientName);
        $clientName = strtoupper($clientName);
        $clientName = preg_replace('/([^A-Z|0-9|\s|\=|\_|\$|\.|\&|\*|\,])/i', '', $clientName);
        $clientName = substr($clientName, 0, 30);

        return $clientName;
    }

    public function validatePayments($payments, $fileType) {
        if (count($payments) == 0) {
            return 'No payments found';
        }

        $duplicate = array();

        $papFileBO = new PapFileBO($this->logger, $this->db);
        $nextBusinessDay = $papFileBO->getBusinessDay(new DateTime(), 1);
        $nextBusinessDay->setTime(0, 0);

        $today = new DateTime();
        $today->setTime(0, 0);

        foreach($payments as $payment) {

            if(is_null($payment['clientName'])) {
                return $payment['mortgageCode'] . ': Client name is empty';
            }

            if(is_null($payment['bank']) || is_null($payment['transit']) || is_null($payment['account'])) {
                return $payment['mortgageCode'] . ': Incomplete bank information';
            }

            if($payment['amount'] <= 0) {
                return $payment['mortgageCode'] . ': Payment amount is zero';
            }

            $dateCheck = DateTime::createFromFormat('Y-m-d', $payment['dueDate']->format('Y-m-d'));
            if($dateCheck === false) {
                return $payment['mortgageCode'] . ': Payment date invalid - Contact IT!';
            }

            if($fileType != 'C') {
                if (isset($duplicate[$payment['mortgageCode'] . $payment['amount'] . $payment['dueDate']->format('Y-m-d')])) {
                    return $payment['mortgageCode'] . ': Duplicate payment found';
                } else {
                    $duplicate[$payment['mortgageCode'] . $payment['amount'] . $payment['dueDate']->format('Y-m-d')] = 'x';
                }
            }

            if ($payment['propertyProvince'] === false) {
                return $payment['mortgageCode'] . ': Property province not found';
            }

            if (isset($payment['dueDate']) && !is_null($payment['dueDate'])) {
                if($payment['dueDate'] <= $today) {
                    return $payment['mortgageCode'] . ': Past Due Payment';
                }
            }
        }

        return true;
    }

    public function updatePapFilePayment($papFileId, $recordNumber, $positionNumber, $payment, $fileType) {
        $papFilePayment = new PapFilePayment();
        $papFilePayment->pap_file_id = $papFileId;
        $papFilePayment->record_number = $recordNumber;
        $papFilePayment->position_number = $positionNumber;
        $papFilePayment->save();

        $userId = Auth::user()->user_id ?? null;

        if ($fileType == 'D') {
            $mortgagePaymentsTable = MortgagePaymentsTable::query()
                ->where('mortgage_id', $payment['mortgageId'])
                ->where('payment_id', $payment['paymentId'])
                ->first();

            if ($mortgagePaymentsTable) {
                $mortgagePaymentsTable->pap_file_payment_id = $papFilePayment->id;
                $mortgagePaymentsTable->updated_by = $userId;
                $mortgagePaymentsTable->save();

                return true;
            }
        } else {
            $papCredit = new PapCredit();
            $papCredit->pap_file_payment_id = $papFilePayment->id;
            $papCredit->institution_code = $payment['bank'];
            $papCredit->transit = $payment['transit'];
            $papCredit->account = $payment['account'];
            $papCredit->client_name = $payment['clientName'];
            $papCredit->amount = $payment['amount'];
            $papCredit->save();

            return true;
        }

        return false;
    }

    public function downloadFile($papFileId) {
        $papFile = PapFile::find($papFileId);

        if ($papFile) {
            $awsBO = new AwsBO($this->logger);
            return $awsBO->getObjectURI($papFile->file_key, $papFile->file_name);
        } else {
            return false;
        }
    }

    public function getBusinessDay(DateTime $currentDate, $daysInAdvance) {
        $tmp = clone $currentDate;

        for ($i = 0; $i < $daysInAdvance; $i++) {
            do {
                $tmp->add(new DateInterval('P1D'));
            } while (!$this->checkValidBusinessDay($tmp));
        }

        return $tmp;
    }

    public function checkValidBusinessDay($date) {
        if ($date->format('D') == 'Sat' || $date->format('D') == 'Sun') {
            return false;
        }

        //check holiday
        $holiday = Holiday::where('holiday_date', $date->format('Y-m-d'))->first();
        if ($holiday) {
            return false;
        }

        return true;
    }

    public function uploadFile($request) {
        if (!$request->hasFile('file')) {
            $this->logger->error('PapFileBO->uploadFile - File is empty');
            return false;
        }

        $file = $request->file('file');
        $fileKey = md5(uniqid());

        if (!$request->hasFile('content')) {
            $this->logger->error('PapFileBO->uploadFile - Parameters are empty');
            return false;
        }

        $fields = json_decode($request->file('content')->get());

        if (!isset($fields->companyId)) {
            $this->logger->error('PapFileBO->uploadFile - companyId not set', [json_encode($fields)]);
            return false;
        }

        $companyId = $fields->companyId;

        //send file to S3
        $awsBO = new AwsBO($this->logger);
        if ($awsBO->uploadFile($fileKey, $file->getPathName()) === false) {
            $this->logger->error('PapFileBO->uploadFile - Uploading file to AWS');
            return false;
        }

        $config = $this->getConfigData($companyId);
        $fileNumber = $this->getNextFileNumber();

        $papFile = new PapFile();
        $papFile->company_id = $companyId;
        $papFile->file_type = 'C';
        $papFile->originator_id = $config['creditOriginatorId'];
        $papFile->file_number = $fileNumber;
        $papFile->file_name = str_pad($fileNumber, 4, '0', 0) . '.txt';
        $papFile->original_file_key = $fileKey;
        $papFile->status = 'R';
        $papFile->save();

        //schedule a batch process to create the pap file
        PapProcessPaymentFileQueue::dispatch($papFile->id);

        return true;
    }

    public function getPaymentsByFileId($papFileId) {
        $query = "select c.mortgage_id, c.mortgage_code, b.processing_date, b.pmt_amt, a.pap_file_id id, 
                         fn_GetSpouse1LastNameByApplicationID(c.application_id) client_last_name,
                         fn_GetSpouse2LastNameByApplicationID(c.application_id) client_last_name2,
                         c.company_id,
                         e.file_number
                    from pap_file_payment a
                    join mortgage_payments_table b on a.id = b.pap_file_payment_id
                    join mortgage_table c on b.mortgage_id = c.mortgage_id
                    join pap_file e on a.pap_file_id = e.id
                   where a.pap_file_id = ?
                order by b.processing_date, client_last_name, c.mortgage_code";
        $res = $this->db->select($query, [$papFileId]);

        $payments = array();
        foreach($res as $value) {
            $payments[] = [
                'mortgageId' => $value->mortgage_id,
                'mortgageCode' => $value->mortgage_code,
                'dueDate' => new DateTime($value->processing_date),
                'amount' => $value->pmt_amt,
                'fileNumber' => $value->file_number,
                'clientLastName' => empty(trim($value->client_last_name)) ? $value->client_last_name2 : $value->client_last_name,
                'companyId' => $value->company_id
            ];
        }

        return $payments;
    }

    public function getChildPayments(
        $mortgageId,
        $dueDate,
        $fileNumber,
        $clientLastName,
        $parentCode
    ) {
        $query = "select a.mortgage_id, a.mortgage_code, b.pmt_amt, a.company_id
                    from mortgage_table a
                    join mortgage_payments_table b on a.mortgage_id = b.mortgage_id
                   where a.parent = ?
                     and b.processing_date = ?
                     and b.payment_id < 10000
                     and b.pmt_amt > 0
                     and a.company_id in (5, 16, 182, 183)
                order by a.mortgage_id, b.processing_date";
        $res = $this->db->select($query, [$mortgageId, $dueDate->format('Y-m-d')]);

        $payments = array();
        foreach($res as $value) {
            $payments[] = [
                'mortgageId' => $value->mortgage_id,
                'mortgageCode' => $value->mortgage_code,
                'dueDate' => $dueDate,
                'amount' => $value->pmt_amt,
                'fileNumber' => $fileNumber,
                'clientLastName' => $clientLastName,
                'companyId' => $value->company_id,
                'parentCode' => $parentCode,
            ];
        }

        return $payments;
    }

    public function netsuite($papFileId) {
        $papFile = PapFile::query()
        ->where('id', $papFileId)
        ->where('status', 'C')
        ->first();

        if(!$papFile) {
            return false;
        }

        $payments = $this->getPaymentsByFileId($papFile->id);

        if(count($payments) == 0) {
            return false;
        }

        if($payments[0]['companyId'] == 1970) {
            return $this->getCsvABL($payments);
        } else {
            return $this->getCsvMIC($payments);
        }
    }

    public function getCsvABL($payments) {
        $csv = "Transaction Type,GL account,File No,tranId,Subsidiary,tranDate,postingPeriodRef,currencyRef,exchangeRate,memo,line memo,itemLine_quantity,Credit,Debit\n";

        $glAccount = '12010';
        $glAccountAB = '11800';

        $childPayments = array();
        foreach($payments as $payment) {
            $childPayments = array_merge($childPayments, $this->getChildPayments(
                $payment['mortgageId'],
                $payment['dueDate'],
                $payment['fileNumber'],
                $payment['clientLastName'],
                $payment['mortgageCode'],
            ));
        }

        if(count($childPayments) == 0) {
            return false;
        }

        //sort child payments by companyId
        $param = array();
        $sortOption = array('desc' => SORT_DESC, 'asc' => SORT_ASC, 'string' => SORT_STRING, 'numeric' => SORT_NUMERIC);

        $columns = array();
        $columns[] = array_column($childPayments, 'companyId');

        $param[] = &$columns[0];
        $param[] = &$sortOption['asc'];
        $param[] = &$childPayments;

        call_user_func_array('array_multisort', $param);

        $companyId = null;
        $subsidiary = null;
        $dueDate = new DateTime();
        $fileNumber = null;
        $total = 0;
        foreach($childPayments as $childPayment) {
            if(!is_null($companyId) && $companyId != $childPayment['companyId']) {
                //total by subsidiary
                $csv .= implode(',', [
                    'PAP',
                    $glAccountAB,
                    '',
                    $fileNumber . '-' . $dueDate->format('d'),
                    $subsidiary,
                    $dueDate->format('m/d/Y'),
                    $dueDate->format('M Y'),
                    'CAD',
                    '1',
                    'PAP #' . $fileNumber,
                    '',
                    '1',
                    '',
                    $total
                ]) . "\n";

                $total = 0;
            }

            $companyId = $childPayment['companyId'];
            $dueDate = $childPayment['dueDate'];
            $fileNumber = $childPayment['fileNumber'];
            $total += $childPayment['amount'];

            switch($companyId) {
                case 182:
                    $subsidiary = '12';
                    break;
                case 183:
                    $subsidiary = '10';
                    break;
                case 5:
                    $subsidiary = '10';
                    break;
                case 16:
                    $subsidiary = '11';
                    break;
                default:
                    $subsidiary = '';
                    break;
            }

            if($childPayment['companyId'] == 5 || $childPayment['companyId'] == 183) {
                $companyAbbr = 'ACIF';
            } else if($childPayment['companyId'] == 16) {
                $companyAbbr = 'ACCIF';
            } else if($childPayment['companyId'] == 182) {
                $companyAbbr = 'ACHYF';
            } else {
                $companyAbbr = '';
            }

            $csv .= implode(',', [
                'PAP',
                $glAccount,
                $childPayment['mortgageCode'],
                $childPayment['fileNumber'] . '-' . $childPayment['dueDate']->format('d') . ' ' . $companyAbbr,
                $subsidiary,
                $childPayment['dueDate']->format('m/d/Y'),
                $childPayment['dueDate']->format('M Y'),
                'CAD',
                '1',
                'PAP #' . $childPayment['fileNumber'],
                $childPayment['clientLastName'] . '|' . $childPayment['parentCode'] . '|' . $childPayment['mortgageCode'],
                '1',
                $childPayment['amount'],
                ''
            ]) . "\n";
        }

        if($total > 0) {
            //total by subsidiary
            $csv .= implode(',', [
                'PAP',
                $glAccountAB,
                '',
                $fileNumber . '-' . $dueDate->format('d'),
                $subsidiary,
                $dueDate->format('m/d/Y'),
                $dueDate->format('M Y'),
                'CAD',
                '1',
                'PAP #' . $fileNumber,
                '',
                '1',
                '',
                $total
            ]) . "\n";
        }

        return $csv;
    }

    public function getCsvMIC($payments) {
        $csv = "Transaction Type,Account Bank,File No,tranId,Subsidiary,tranDate,postingPeriodRef,currencyRef,exchangeRate,memo,line memo,itemLine_quantity,Payment Amount,GL account\n";

        foreach($payments as $payment) {
            $accountBank = '';
            $glAccount = '';
            $subsidiary = '';

            switch($payment['companyId']) {
                case 182:
                    $accountBank = '10750';
                    $glAccount = 'Borrower Payment';
                    $subsidiary = '12';
                    break;
                case 183:
                    $accountBank = '10660';
                    $glAccount = 'Borrower Payment - RQL';
                    $subsidiary = '10';
                    break;
                case 5:
                    $accountBank = '10660';
                    $glAccount = 'Borrower Payment';
                    $subsidiary = '10';
                    break;
                case 16:
                    $accountBank = '10700';
                    $glAccount = 'Borrower Payment';
                    $subsidiary = '11';
                    break;
                default:
                    return false;
            }

            $csv .= implode(',', [
                'PAP',
                $accountBank,
                $payment['mortgageCode'],
                $payment['fileNumber'] . '-' . $payment['dueDate']->format('d'),
                $subsidiary,
                $payment['dueDate']->format('m/d/Y'),
                $payment['dueDate']->format('M Y'),
                'CAD',
                '1',
                'PAP #' . $payment['fileNumber'],
                $payment['clientLastName'] . '|' . $payment['mortgageCode'],
                '1',
                $payment['amount'],
                $glAccount
            ]) . "\n";
        }

        return $csv;
    }

    public function quickbooks($papFileId) {

        $papFile = PapFile::query()
            ->where('id', $papFileId)
            ->where('status', 'C')
            ->first();

        if (!$papFile) {
            return false;
        }

        $payments = $this->getPaymentsByFileId($papFile->id);

        $subtotals = array();
        foreach ($payments as $key => $payment) {
            if (isset($subtotals[$payment['dueDate']->format('Ymd')])) {
                $subtotals[$payment['dueDate']->format('Ymd')] += $payment['amount'];
            } else {
                $subtotals[$payment['dueDate']->format('Ymd')] = $payment['amount'];
            }
        }

        $fileKey = md5(uniqid());
        Storage::append('tmp/' . $fileKey, $this->getQBHeader());

        $dueDate = '';
        foreach ($payments as $key => $payment) {
            if ($dueDate != $payment['dueDate']->format('Ymd')) {
                if (!empty($dueDate)) {
                    Storage::append('tmp/' . $fileKey, $this->getQBEndTrans());
                }
                Storage::append('tmp/' . $fileKey, $this->getQBStartTrans($papFile->company_id, $payment['dueDate'], $subtotals[$payment['dueDate']->format('Ymd')], $papFile->file_number));
            }
            $companyId = $payment['companyId'];
            Storage::append('tmp/' . $fileKey, $this->getQBTrans($payment['dueDate'], $payment['amount'], $payment['clientLastName'], $payment['mortgageCode'], $companyId));

            $dueDate = $payment['dueDate']->format('Ymd');
        }

        if (count($payments) > 0) {
            Storage::append('tmp/' . $fileKey, $this->getQBEndTrans());
        }

        Storage::append('tmp/' . $fileKey, ''); //empty line

        return base64_encode(Storage::get('tmp/' . $fileKey));
    }

    public function getQBStartTrans($companyId, DateTime $date, $amount, $fileNumber) {
        if ($companyId == 5) {
            $record = "TRNS\t\tDEPOSIT\t[tag_date]\t1008-01 " . hex2bin('b7') . " BMO General Account\tpap\t\t[tag_amount]\t\tPAP #[tag_file_id]\t\t \t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r";
        } elseif ($companyId == 182) {
            $record = "TRNS\t\tDEPOSIT\t[tag_date]\t1008-01 " . hex2bin('b7') . " BMO General Account\tpap\t\t[tag_amount]\t\tPAP #[tag_file_id]\t\t \t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r";
        } else {
            $record = "TRNS\t\tDEPOSIT\t[tag_date]\t1008-01 " . hex2bin('b7') . " BMO General Account\tpap\t\t[tag_amount]\t\tPAP #[tag_file_id]\t\t \t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r";
        }
        $record = str_replace('[tag_date]', $date->format('n/j/Y'), $record);
        $record = str_replace('[tag_amount]', $amount, $record);
        $record = str_replace('[tag_file_id]', $fileNumber, $record);

        return $record;
    }

    public function getQBEndTrans() {
        return "ENDTRNS\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r";
    }

    public function getQBTrans($date, $amount, $lastName, $mortgageCode, $companyId) {

        if ($companyId == 183) {
            $record = "SPL\t\tDEPOSIT\t[tag_date]\t1403 " . hex2bin('b7') . " Mortgage on Mortgage QC\tpap\t\t[tag_amount]\t\t[tag_last_name] [tag_mortgage_code]\t \t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r";
        } else {
            $record = "SPL\t\tDEPOSIT\t[tag_date]\t1401 " . hex2bin('b7') . " Mortgage Receivable\tpap\t\t[tag_amount]\t\t[tag_last_name] [tag_mortgage_code]\t \t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r";
        }
        $record = str_replace('[tag_date]', $date->format('n/j/Y'), $record);
        $record = str_replace('[tag_amount]', $amount * -1, $record);
        $record = str_replace('[tag_last_name]', $lastName, $record);
        $record = str_replace('[tag_mortgage_code]', $mortgageCode, $record);

        return $record;
    }

    public function getQBHeader() {

        $header = "!ACCNT\tNAME\tACCNTTYPE\tDESC\tACCNUM\tEXTRA\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r\n";
        $header .= "ACCNT\tChecking\tBANK\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r\n";
        $header .= "ACCNT\tIncome\tINC\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r\n";
        $header .= "!CLASS\tNAME\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r\n";
        $header .= "CLASS\tclass\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r\n";
        $header .= "!CUST\tNAME\tBADDR1\tBADDR2\tBADDR3\tBADDR4\tBADDR5\tSADDR1\t\tSADDR2\tSADDR3\tSADDR4\tSADDR5\tPHONE1\tPHONE2\tFAXNUM\tEMAIL\tNOTE\tCONT1\tCONT2\tCTYPE\tTERMS\tTAXABLE\tLIMIT\tRESALENUM\tREP\tTAXITEM\tNOTEPAD\tSALUTATION\tCOMPANYNAME\tFIRSTNAME\tMIDINIT\tLASTNAME\r\n";
        $header .= "CUST\tCustomer\tJoe Customer\t444 Road Rd\t\"Anywhere, AZ 85740\"\tUSA\t\t\t\t\t\t\t\t5554443333\t\t\t\t\tJoe Customer\t\t\t\tN\t\t\t\t\t\t\t\tJoe\t\tCustomer\r\n";
        $header .= "!OTHERNAME\tNAME\tBADDR1\tBADDR2\tBADDR3\tBADDR4\tBADDR5\tPHONE1\tPHONE2\tFAXNUM\tEMAIL\tNOTE\tCONT1\tCONT2\tNOTEPAD\tSALUTATION\tCOMPANYNAME\tFIRSTNAME\tMIDINIT\tLASTNAME\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r\n";
        $header .= "OTHERNAME\tOther Name\tOther Name\t123 a Street\t\"Somewhere, AZ 85730\"\tUSA\t\t5555555555\t\t\t\t\tOther Name\t\t\t\t\tOther\t\tName\t\t\t\t\t\t\t\t\t\t\t\t\t\r\n";
        $header .= "!TRNS\tTRNSID\tTRNSTYPE\tDATE\tACCNT\tNAME\tCLASS\tAMOUNT\tDOCNUM\tMEMO\tCLEAR\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r\n";
        $header .= "!SPL\tSPLID\tTRNSTYPE\tDATE\tACCNT\tNAME\tCLASS\tAMOUNT\tDOCNUM\tMEMO\t \t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\r"; //should not have \n as it will be added by the Storage class

        return $header;
    }

    public function changeIntervalDays($intervalDays) {
        $this->intervalDays = $intervalDays;
    }
}
