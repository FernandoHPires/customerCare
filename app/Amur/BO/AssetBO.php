<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\AssetTable;

class AssetBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getDataByApplicationId($id) {
        $data = AssetTable::query()
        ->where('application_id', $id)
        ->orderBy('asset_id', 'asc')
        ->get();
        
        $assets = array();
        foreach($data as $key => $value) {
            $assets[] = [
                'id' => $value->asset_id,
                'description' => $value->type,
                'amount' => $value->amount,
                'isRemoved' => false
            ];
        }

        return $assets;
    }

    public function store($assets, $applicationId) {

        $this->logger->info('AssetBO->store',[$applicationId]);

        $this->db->beginTransaction();

        try {
            foreach($assets as $key => $asset) {

                if(isset($asset['isRemoved']) && $asset['isRemoved']) {
                    AssetTable::query()
                    ->where('asset_id', $asset['id'])
                    ->delete();

                } else {
                    $assetObj = AssetTable::find($asset['id']);

                    if(!$assetObj) {
                        $assetObj = new AssetTable();
                        $assetObj->application_id = $applicationId;
                    }   

                    $amountTmp = 0;
                    if (isset($asset['amount'])) {
                        $amountTmp = str_replace(',','',$asset['amount']);
                    }

                    $assetObj->type   = ($asset['description'] ?? '');
                    $assetObj->amount = $amountTmp;
                    $assetObj->save();
                }           
            }

        } catch(\Throwable $e) {
            $this->logger->error('AssetBO->store', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;
    }
}
