<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\AUni\Factory\Factory;

class PapProcessPaymentFileQueue implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    private $papFileId;

    public function __construct($papFileId) {
        $this->queue = 'tacl-next-gen';
        $this->papFileId = $papFileId;
    }

    public function handle() {
        $logger = Factory::create('Bean\\Logger');
        $db = Factory::create('Bean\\DB');

        $logger->info('PapProcessPaymentFileQueue->handle');

        try {
            $papFileBO = Factory::create('BO\\PapFileBO', $logger, $db);
            $papFileBO->processPaymentFile($this->papFileId);
        } catch(\Exception $e) {
            $logger->error('PapProcessPaymentFileQueue->handle - Job failed',[$e->getMessage(),$e->getTraceAsString()]);
            $this->delete();
        }
    }
}