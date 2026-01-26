<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Amur\Bean\Logger;
use App\Amur\Bean\Response;
use App\Amur\BO\BayviewBO;
use Illuminate\Http\Request;
use App\Amur\Utilities\Loan;
use DateTime;
use Illuminate\Support\Facades\Storage;
use gnupg;
use App\Amur\Routine\MortgageTask;
use App\Amur\BO\PapFileDailyReportBO;
use App\Amur\BO\StagePipelineBO;
use DateInterval;
use DateTimeZone;

class TestController extends Controller {

    /** @var ILogger */
    private $logger;
    /** @var IDB */
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    public function test() {
        $date = new DateTime('2022-06-01');

        $response = [
            Loan::isLockedDate(5, $date),
            $date->format('Y-m-d')
        ];
        return response()->json($response, 200);
    }

    public function dailyPaymentRun() {
        $mortgageTask = new MortgageTask($this->logger, $this->db);
        $mortgageTask->dailyPaymentRun();
    }

    public function papDailyReport() {
        $report = new PapFileDailyReportBO($this->logger, $this->db);
        $response = $report->papDailyReport();

        return response()->json($response, 200);
    }

    public function bankInfoReminder() {
        $report = new PapFileDailyReportBO($this->logger, $this->db);
        $response = $report->bankInfoReminder();

        return response()->json($response, 200);
    }

    public function pgpEncrypt() {
        $response = [];

        $publicKey = Storage::disk('local')->get('tmp/pubKey4.txt');

        $gpg = new gnupg();
        $gpg->seterrormode(gnupg::ERROR_EXCEPTION);
        $info = $gpg->import($publicKey);
        $gpg->addencryptkey($info['fingerprint']);
        $gpg->setarmor(0);

        //list all keys in the keyring
        $response = $gpg->keyinfo('');

        $fileName = '5e98825ba5ab7580234c61db6f4a06d7.csv';
        $file = Storage::disk('local')->get('tmp/' . $fileName);
        $encryptFile = $gpg->encrypt($file);
        Storage::disk('local')->put('tmp/' . $fileName . '.gpg', $encryptFile);

        return response()->json($response, 200);
    }

    public function transferDailyReports(Request $request) {

        $bayviewBO = new BayviewBO($this->logger, $this->db);
        $res = $bayviewBO->transferDailyReports();

        $response = new Response();
        $response->status = 'success';
        $response->message = '';

        return response()->json($response, 200);
    }

    // public function charts(Request $request)
    // {
    //     $chartData1 = array();
    //     $startDate = new DateTime('2025-10-01');
    //     $startDate->setTimezone(new DateTimeZone('UTC'));
    //     $startDate->setTime(0, 0, 0);

    //     $endDate = new DateTime('2025-10-31');
    //     $endDate->setTimezone(new DateTimeZone('UTC'));
    //     $endDate->setTime(0, 0, 0);

    //     $data = array();
    //     $tmp = clone $startDate;
    //     while ($tmp < $endDate) {
    //         $unixTimestamp = intval($tmp->getTimestamp() . '000');
    //         $data[] = [$unixTimestamp, rand(50, 100)];

    //         $tmp->add(new DateInterval('P1D'));
    //     }

    //     $chartData1[] = [
    //         'name' => 'Init Docs',
    //         'data' => $data
    //     ];

    //     $data = array();
    //     $tmp = clone $startDate;
    //     while ($tmp < $endDate) {
    //         $unixTimestamp = intval($tmp->getTimestamp() . '000');
    //         $data[] = [$unixTimestamp, rand(50, 100)];

    //         $tmp->add(new DateInterval('P1D'));
    //     }

    //     $chartData1[] = [
    //         'name' => 'Signing',
    //         'data' => $data
    //     ];

    //     $data = array();
    //     $tmp = clone $startDate;
    //     while ($tmp < $endDate) {
    //         $unixTimestamp = intval($tmp->getTimestamp() . '000');
    //         $data[] = [$unixTimestamp, rand(50, 100)];

    //         $tmp->add(new DateInterval('P1D'));
    //     }

    //     $chartData1[] = [
    //         'name' => 'Funding',
    //         'data' => $data
    //     ];

    //     $response = new Response();
    //     $response->status = 'success';
    //     $response->message = '';
    //     $response->data = [
    //         'chartData1' => $chartData1,
    //     ];

    //     return response()->json($response, 200);
    // }

    public function charts(Request $request) {
        $startDate = new DateTime($request->startDate);
        $startDate->setTimezone(new DateTimeZone('UTC'));
        $startDate->setTime(0, 0, 0);

        $endDate = new DateTime($request->endDate);
        $endDate->setTimezone(new DateTimeZone('UTC'));
        $endDate->setTime(0, 0, 0);

        $generateChartData = function ($startDate, $endDate, $minRange, $maxRange) {
            $chartData = array();
            $seriesNames = ['Init Docs', 'Signing', 'Funding'];

            foreach($seriesNames as $name) {
                $data = array();
                $tmp = clone $startDate;

                while($tmp <= $endDate) {
                    // Highcharts expects milliseconds, so we append '000'
                    $unixTimestamp = intval($tmp->getTimestamp() . '000');
                    if($name == 'Init Docs') {
                        $data[] = [$unixTimestamp, rand($minRange, $maxRange)];
                    } elseif($name == 'Signing') {
                        $data[] = [$unixTimestamp, rand($minRange - 20, $maxRange - 20)];
                    } else {
                        $data[] = [$unixTimestamp, rand($minRange - 30, $maxRange - 30)];
                    }

                    $tmp->add(new DateInterval('P1D'));
                }

                $chartData[] = [
                    'name' => $name,
                    'data' => $data
                ];
            }

            return $chartData;
        };

        $chartData1 = $generateChartData($startDate, $endDate, 80, 100);
        $chartData2 = $generateChartData($startDate, $endDate, 30, 80);

        $response = new Response();
        $response->status = 'success';
        $response->message = '';
        $response->data = [
            'chartData1' => $chartData1,
            'chartData2' => $chartData2, // STEP 2: Include chartData2 in the API response
        ];

        return response()->json($response, 200);
    }

    public function drillDown(Request $request) {
        $seriesName = $request->input('series');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        // For now, return hardcoded data as requested
        // In a real implementation, this would query the database
        $drillDownData = $this->getHardcodedDrillDownData($seriesName);

        $response = new Response();
        $response->status = 'success';
        $response->message = '';
        $response->data = $drillDownData;

        return response()->json($response, 200);
    }

    private function getHardcodedDrillDownData($seriesName) {
        // Hardcoded data for demonstration
        $sampleData = [
            'Init Docs' => [
                ['applicationNumber' => '110987', 'status' => 'Closed', 'closeReason' => 'Fee too high'],
                ['applicationNumber' => '780569', 'status' => 'Closed', 'closeReason' => 'Death by appraisal'],
                ['applicationNumber' => '998765', 'status' => 'Prospecting', 'closeReason' => null],
                ['applicationNumber' => '443568', 'status' => 'Prospecting', 'closeReason' => null]
            ],
            'Signing' => [
                ['applicationNumber' => '601230', 'status' => 'Funded', 'closeReason' => 'Successfully signed'],
                ['applicationNumber' => '285432', 'status' => 'Closed', 'closeReason' => 'Client withdrew'],
                ['applicationNumber' => '330976', 'status' => 'Quote', 'closeReason' => null]
            ],
            'Funding' => [
                ['applicationNumber' => '121345', 'status' => 'Funded', 'closeReason' => 'Loan disbursed'],
                ['applicationNumber' => '5780976', 'status' => 'Funded', 'closeReason' => null],
                ['applicationNumber' => '447689', 'status' => 'Signing', 'closeReason' => null],
                ['applicationNumber' => '123907', 'status' => 'Quote', 'closeReason' => null],
                ['applicationNumber' => '889345', 'status' => 'Quote', 'closeReason' => null]
            ]
        ];

        return $sampleData[$seriesName] ?? [];
    }

    public function loadStagePipeline() {
        $stagePipelineBO = new StagePipelineBO($this->logger, $this->db);
        
        try {
            $stats = $stagePipelineBO->loadData();
            
            $response = new Response();
            $response->status = 'success';
            $response->message = 'Stage pipeline data loaded successfully';
            $response->data = $stats;
            
            return response()->json($response, 200);
        } catch (\Exception $e) {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error loading stage pipeline data: ' . $e->getMessage();
            
            return response()->json($response, 500);
        }
    }

    public function populateStagePipelineTestData() {
        $stagePipelineBO = new StagePipelineBO($this->logger, $this->db);
        
        try {
            // Generate test data for the last 7 days
            $dates = [];
            $today = new DateTime();
            
            for ($i = 0; $i < 20; $i++) {
                $date = clone $today;
                $date->modify("-{$i} days");
                $dates[] = $date->format('Y-m-d');
            }

            $insertedCount = 0;
            
            foreach ($dates as $date) {
                // Generate random test data for each date
                // Initial Docs (status_id: 8, 17)
                $initialDocsCount = rand(80, 120);
                for ($i = 0; $i < $initialDocsCount; $i++) {
                    $statusId = (rand(0, 1) == 0) ? 8 : 17; // Randomly pick 8 or 17
                    $data = [
                        'mortgage_id' => rand(1000, 9999),
                        'saved_quote_id' => rand(1000, 9999),
                        'status_id' => $statusId,
                        'reference_date' => $date
                    ];
                    if ($this->db->insert('stage_pipeline', $data)) {
                        $insertedCount++;
                    }
                }

                // Signing (status_id: 10, 14)
                $signingCount = rand(10, 30);
                for ($i = 0; $i < $signingCount; $i++) {
                    $statusId = (rand(0, 1) == 0) ? 10 : 14; // Randomly pick 10 or 14
                    $data = [
                        'mortgage_id' => rand(1000, 9999),
                        'saved_quote_id' => rand(1000, 9999),
                        'status_id' => $statusId,
                        'reference_date' => $date
                    ];
                    if ($this->db->insert('stage_pipeline', $data)) {
                        $insertedCount++;
                    }
                }

                // Funding (status_id: 13)
                $fundingCount = rand(50, 80);
                for ($i = 0; $i < $fundingCount; $i++) {
                    $data = [
                        'mortgage_id' => rand(1000, 9999),
                        'saved_quote_id' => rand(1000, 9999),
                        'status_id' => 13,
                        'reference_date' => $date
                    ];
                    if ($this->db->insert('stage_pipeline', $data)) {
                        $insertedCount++;
                    }
                }
            }

            $response = new Response();
            $response->status = 'success';
            $response->message = "Test data populated successfully for " . count($dates) . " dates";
            $response->data = [
                'dates_populated' => $dates,
                'total_records_inserted' => $insertedCount
            ];
            
            return response()->json($response, 200);
        } catch (\Exception $e) {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error populating test data: ' . $e->getMessage();
            
            return response()->json($response, 500);
        }
    }
}
