<?php

namespace App\Console;

use App\Amur\Bean\DB;
use App\Amur\Bean\Logger;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Amur\BO\BayviewBO;
use App\Amur\BO\PapFileDailyReportBO;
use App\Amur\BO\CMS\CommissionSetupBO;
use App\Amur\BO\PapUtilBO;
use App\Amur\BO\ReportBO;
use App\Amur\Routine\MortgageTask;
use App\Amur\BO\PapBO;
use App\Amur\BO\StagePipelineBO;

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