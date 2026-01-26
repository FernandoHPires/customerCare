<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PapController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\TestController;

Route::group(['middleware' => 'apiAuthentication'], function() {

    Route::post('payout-create',[PayoutController::class, 'createPayout']);
    Route::get('pap/transactions/process/{id}', [PapController::class, 'processTransactionApi']);
    Route::get('pap/notification/{id}', [PapController::class, 'transactionNotification']);

    //Manual
    Route::post('payout/confirm', [PayoutController::class, 'processPayout']);

});


Route::get('test/period', [TestController::class, 'test']);
Route::get('test/daily-payment-run', [TestController::class, 'dailyPaymentRun']);
Route::get('test/pap-daily-report', [TestController::class, 'papDailyReport']);
Route::get('test/pgp', [TestController::class, 'pgpEncrypt']);
Route::get('test/transfer-reports-bv', [TestController::class, 'transferDailyReports']);
Route::get('test/load-stage-pipeline', [TestController::class, 'loadStagePipeline']);
Route::get('test/populate-stage-pipeline-test-data', [TestController::class, 'populateStagePipelineTestData']);

