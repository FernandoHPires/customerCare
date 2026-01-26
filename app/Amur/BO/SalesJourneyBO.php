<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\SalesJourney;
use Illuminate\Support\Facades\Auth;
use App\Models\GroupMembersTable;
use App\Amur\Utilities\HttpRequest;

class SalesJourneyBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getActiveSalesJourney($applicationId) {

        $data = SalesJourney::query()
        ->where('application_id',$applicationId)
        ->orderBy('id', 'desc')
        ->first();

        $salesJourney = [
            'id' => 0,
            'referringAgentId' => 0,
            'brokerId' => 0,
            'updateSalesJourney' => false,
        ];
        
        if($data) {
            if($data->status_id != 18 && $data->status_id != 9) {
                $salesJourney = [
                    'id' => $data->id,
                    'referringAgentId' => $data->referring_agent_id,
                    'brokerId' => $data->broker_id,
                    'updateSalesJourney' => $this->checkPermission(),
                ];

                return $salesJourney;
            }
        }

        return $salesJourney;
    }

    public function updateAgents($salesJourney, $applicationId) {

        $this->logger->info('SalesJourneyBO->updateAgents',[$applicationId]);

        $this->db->beginTransaction();

        try {

            $salesJourneyData = SalesJourney::find($salesJourney['id']);

            if ($salesJourneyData) {
                
                $salesJourneyData->referring_agent_id = $salesJourney['referringAgentId'];
                $salesJourneyData->broker_id = $salesJourney['brokerId'];
                $salesJourneyData->save();
            }

        } catch (\Throwable $e) {
            $this->logger->error('SalesJourneyBO->updateAgents', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;

    }

    public function checkPermission() {
        
        $userId = Auth::user()->user_id;

        $this->logger->info('SalesJourneyBO->checkPermission',[$userId]);

        $groupMembersTable = GroupMembersTable::query()
        ->where('user_id', $userId)
        ->where('group_id', 47)
        ->where('deleted', 0)
        ->first();

        if($groupMembersTable) {
            return true;
        } else {
            return false;
        }
    }

    public function updateSalesJourney($salesJourneyId, $field, $value) {
        $userId = Auth::user()->user_id;

        $this->logger->info('SalesJourneyBO->updateSalesJourney', [$salesJourneyId, $field, $value, $userId]);
        
        $salesJourney = SalesJourney::find($salesJourneyId);

        if(!$salesJourney) {
            return [
                'status' => false,
                'message' => 'Sales Journey not found'
            ];
        }

        $fields = array(
            'salesJourneyId' => $salesJourneyId,
            'applicationId' => $salesJourney->application_id,
            'field'    => $field,
            'value'    => $value,
            'userId'   => $userId
        );

        $amurEndpoint = env('AMUR_API_ENDPOINT');

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($amurEndpoint . '/webhook/salesforce/update-sales-journey-agents');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->exec();

        $response = json_decode($httpRequest->getResponse());

        if($response->status == 'success') {
            return [
                'status' => true,
                'message' => ''
            ];
        } else {
            $this->logger->error('SalesJourneyBO->updateSalesJourney', [$response->message]);
            return [
                'status' => false,
                'message' => $response->message
            ];
        }
    }
}
