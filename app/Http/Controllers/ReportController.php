<?php

namespace App\Http\Controllers;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Amur\Bean\Response;
use App\Amur\BO\ReportBO;
use App\Amur\BO\BayviewBO;
use App\Amur\BO\MicForecastBO;
use App\Amur\BO\StagePipelineBO;
use DateTime;
use DateTimeZone;

class ReportController extends Controller {

    private $logger;
    private $db;

    public function __construct() {
        $this->logger = new Logger();
        $this->db = new DB();
    }

    /*public function bayviewCommitment(Request $request) {

        $bayviewBO = new BayviewBO($this->logger, $this->db);
        $res = $bayviewBO->getCommitmentReport();

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting data';
        }

        return response()->json($response, 200);
    }

    public function bayviewCommitmentDownload(Request $request) {

        $bayviewBO = new BayviewBO($this->logger, $this->db);
        $res = $bayviewBO->getCommitmentReport($request->type);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting data';
        }

        return response()->json($response, 200);
    }

    public function bayviewAcquisition(Request $request) {

        $bayviewBO = new BayviewBO($this->logger, $this->db);
        $res = $bayviewBO->getAcquisitionReport();

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting data';
        }

        return response()->json($response, 200);
    }

    public function bayviewAcquisitionDownload(Request $request) {

        $bayviewBO = new BayviewBO($this->logger, $this->db);
        $res = $bayviewBO->getAcquisitionReport($request->type);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting data';
        }

        return response()->json($response, 200);
    }

    public function bayviewTrialBalance(Request $request) {

        $bayviewBO = new BayviewBO($this->logger, $this->db);
        $res = $bayviewBO->getTrialBalanceReport();

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting data';
        }

        return response()->json($response, 200);
    }

    public function bayviewTrialBalanceDownload(Request $request) {

        $bayviewBO = new BayviewBO($this->logger, $this->db);
        $res = $bayviewBO->getTrialBalanceReport($request->type);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting data';
        }

        return response()->json($response, 200);
    }

    public function bayviewRemittance(Request $request) {

        $bayviewBO = new BayviewBO($this->logger, $this->db);
        $res = $bayviewBO->getRemittanceReport();

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting data';
        }

        return response()->json($response, 200);
    }

    public function bayviewRemittanceDownload(Request $request) {

        $bayviewBO = new BayviewBO($this->logger, $this->db);
        $res = $bayviewBO->getRemittanceReport($request->type);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting data';
        }

        return response()->json($response, 200);
    }*/

    public function index(Request $request) {
        $userId = Auth::user()->user_id;

        $reportBO = new ReportBO($this->logger, $this->db);
        $reports = $reportBO->index($userId);

        $response = new Response();
        $response->status = 'success';
        $response->data = $reports;

        return response()->json($response, 200);
    }

    public function saveReportFavourites(Request $request) {

        $userId = Auth::user()->user_id;
        $reportId = $request->reportId;

        $reportBO = new ReportBO($this->logger, $this->db);
        $reportBO->saveReportFavourites($userId, $reportId);

        $response = new Response();
        $response->status = 'success';
        $response->message = 'Successfully added/removed the report from favourites';

        return response()->json($response, 200);
    }

    public function micPipeline() {

        $reportBO = new ReportBO($this->logger, $this->db);
        $pipeline = $reportBO->getMicPipeline();

        $response = new Response();
        $response->status = 'success';
        $response->data = [
            'pipeline' => $pipeline,
        ];

        return response()->json($response, 200);
    }

    public function initialDocsReport(Request $request) {
        $startDate = new DateTime($request->startDate);
        $endDate = new DateTime($request->endDate);
        $startDate->setTime(0, 0, 0);
        $endDate->setTime(23, 59, 59);

        $reportBO = new ReportBO($this->logger, $this->db);
        $res = $reportBO->getInitialDocsReport($startDate, $endDate);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting data';
        }

        return response()->json($response, 200);
    }

    public function commercialLoansTracker(Request $request) {

        $reportBO = new ReportBO($this->logger, $this->db);
        $res = $reportBO->commercialLoansTracker();

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting data';
        }

        return response()->json($response, 200);
    }

    public function micForecast(Request $request) {

        $fundingBuffer = $request->fundingBuffer;
        $signingBuffer = $request->signingBuffer;
        $initialDocsBuffer = $request->initialDocsBuffer;

        $micForecastBO = new MicForecastBO($this->logger, $this->db);
        $micForecast = $micForecastBO->getMicForecast($fundingBuffer, $signingBuffer, $initialDocsBuffer);

        $response = new Response();
        $response->status = 'success';
        $response->data = $micForecast;

        return response()->json($response, 200);
    }

    public function originationPipeline(Request $request) {
        $endDate = $request->endDate;

        $reportBO = new ReportBO($this->logger, $this->db);
        $originationPipeline = $reportBO->getOriginationPipeline(new DateTime($endDate));

        $response = new Response();
        $response->status = 'success';
        $response->data = $originationPipeline;

        return response()->json($response, 200);
    }

    public function brokerDashboard(Request $request) {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $province = $request->get('province');

        $reportBO = new ReportBO($this->logger, $this->db);
        $res = $reportBO->brokerDashboard($month, $year, $province);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting broker dashboard data';
        }

        return response()->json($response, 200);
    }

    public function pipelineForecast(Request $request) {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $province = $request->get('province');

        $reportBO = new ReportBO($this->logger, $this->db);
        $res = $reportBO->getPipelineForecast($month, $year, $province);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting pipeline forecast data';
        }

        return response()->json($response, 200);
    }

    public function brokerDetails(Request $request) {
        $userId = $request->get('userId');
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        if (!$userId) {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'User ID is required';
            return response()->json($response, 400);
        }

        $reportBO = new ReportBO($this->logger, $this->db);
        $res = $reportBO->brokerDetails($userId, $month, $year);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting broker details data';
        }

        return response()->json($response, 200);
    }

    public function pbBreakdown(Request $request) {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $province = $request->get('province');

        $reportBO = new ReportBO($this->logger, $this->db);
        $res = $reportBO->pbBreakdown($month, $year, $province);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting PB breakdown data';
        }

        return response()->json($response, 200);
    }

    public function nbBreakdown(Request $request) {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $province = $request->get('province');

        $reportBO = new ReportBO($this->logger, $this->db);
        $res = $reportBO->nbBreakdown($month, $year, $province);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting NB breakdown data';
        }

        return response()->json($response, 200);
    }

    public function ltBreakdown(Request $request) {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $province = $request->get('province');

        $reportBO = new ReportBO($this->logger, $this->db);
        $res = $reportBO->ltBreakdown($month, $year, $province);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting LT breakdown data';
        }

        return response()->json($response, 200);
    }

    public function allBreakdown(Request $request) {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $province = $request->get('province');

        $reportBO = new ReportBO($this->logger, $this->db);
        $res = $reportBO->allBreakdown($month, $year, $province);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error getting All breakdown data';
        }

        return response()->json($response, 200);
    }

    public function stagePipeline(Request $request) {
        $startDate = new DateTime($request->startDate);
        $startDate->setTimezone(new DateTimeZone('UTC'));
        $startDate->setTime(0, 0, 0);

        $endDate = new DateTime($request->endDate);
        $endDate->setTimezone(new DateTimeZone('UTC'));
        $endDate->setTime(0, 0, 0);

        $stagePipelineBO = new StagePipelineBO($this->logger, $this->db);
        $res = $stagePipelineBO->index($startDate, $endDate);

        if ($res !== false) {
            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Could not retrieve stage pipeline data';
        }

        return response()->json($response, 200);
    }

    public function getStagePipelineCounts(Request $request) {
        $month = $request->month;
        $year = $request->year;

        $stagePipelineBO = new StagePipelineBO($this->logger, $this->db);
        $res = $stagePipelineBO->getCounts($month, $year);

        $response = new Response();
        if($res !== false) {
            $response->status = 'success';
            $response->message = '';
            $response->data = $res;
        } else {
            $response->status = 'error';
            $response->message = 'Error retrieving stage pipeline counts: ' . $e->getMessage();
        }

        return response()->json($response, 200);
    }

    public function compareStagePipelineDates(Request $request) {
        $date1 = $request->input('date1');
        $date2 = $request->input('date2');

        if (!$date1 || !$date2) {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Both date1 and date2 parameters are required';

            return response()->json($response, 400);
        }

        $stagePipelineBO = new StagePipelineBO($this->logger, $this->db);
        
        try {
            $comparison = $stagePipelineBO->compareDates($date1, $date2);

            $response = new Response();
            $response->status = 'success';
            $response->message = '';
            $response->data = $comparison;

            return response()->json($response, 200);
        } catch (\Exception $e) {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'Error comparing stage pipeline dates: ' . $e->getMessage();

            return response()->json($response, 500);
        }
    }
}
