<?php

namespace App\Console;

use App\AUni\Bean\DB;
use App\AUni\Bean\Logger;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\AUni\BO\BayviewBO;
use App\AUni\BO\PapFileDailyReportBO;
use App\AUni\BO\CMS\CommissionSetupBO;
use App\AUni\BO\PapUtilBO;
use App\AUni\BO\ReportBO;
use App\AUni\Routine\MortgageTask;
use App\AUni\BO\PapBO;
use App\AUni\BO\StagePipelineBO;

class Kernel extends ConsoleKernel {
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    
    protected function schedule(Schedule $schedule) {

        $schedule->call(function() {
            if(env('APP_ENV') == 'production') {
                $db = new DB();
                $logger = new Logger();

                $mortgageTask = new MortgageTask($logger, $db);
                $mortgageTask->dailyPaymentRun();
            }
        })->dailyAt('00:45');

        $schedule->call(function() {
            if(env('APP_ENV') == 'production') {
                $db = new DB();
                $logger = new Logger();

                $papFileDailyReportBO = new PapFileDailyReportBO($logger, $db);
                $papFileDailyReportBO->bankInfoReminder();
            }
        })->dailyAt('07:00');

        $schedule->call(function() {
            if(env('APP_ENV') == 'production') {
                $db = new DB();
                $logger = new Logger();

                $papFileDailyReportBO = new PapFileDailyReportBO($logger, $db);
                $papFileDailyReportBO->papDailyReport();
            }
        })->dailyAt('09:00');

        $schedule->call(function() {
            if(env('APP_ENV') == 'production') {
                $db = new DB();
                $logger = new Logger();

                $papUtilBO = new PapUtilBO($logger, $db);
                $papUtilBO->checkPayments('First');
            }

        })->dailyAt('14:15');

        $schedule->call(function() {
            if(env('APP_ENV') == 'production') {
                $db = new DB();
                $logger = new Logger();

                $papUtilBO = new PapUtilBO($logger, $db);
                $papUtilBO->checkPayments('Second');
            }
        })->dailyAt('14:45');

        $schedule->call(function() {
            if(env('APP_ENV') == 'production') {
                //$db = new DB();
                //$logger = new Logger();

                //$bayviewBO = new BayviewBO($logger, $db);
                //$bayviewBO->transferDailyReports();
            }
        })->dailyAt('17:00');

        $schedule->call(function () {
            if (env('APP_ENV') == 'production') {
                $db = new DB();
                $logger = new Logger();
                $report = new CommissionSetupBO($logger, $db);
                $report->approveAllAgents();
            }
        })->dailyAt('17:00');

        $schedule->call(function () {
            if (env('APP_ENV') == 'production') {
                $db = new DB();
                $logger = new Logger();
                $reportBO = new ReportBO($logger, $db);
                $reportBO->fillDailyPipelineTable();
            }
        })->dailyAt('01:00');

        $schedule->call(function() {
            if(env('APP_ENV') == 'production') {
                $db = new DB();
                $logger = new Logger();

                $papBO = new PapBO($logger, $db);
                $papBO->checkTransactions();
            }
        })->dailyAt('05:00');

        $schedule->call(function() {
            if(env('APP_ENV') == 'production') {
                $db = new DB();
                $logger = new Logger();

                $stagePipelineBO = new StagePipelineBO($logger, $db);
                $stagePipelineBO->loadData();
            }
        })->dailyAt('06:00');

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands() {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}