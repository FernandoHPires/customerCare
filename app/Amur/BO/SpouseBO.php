<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use App\Models\SpouseTable;
use App\Amur\Utilities\ConvertDate;
use App\Models\ApplicantTable;
use Carbon\Carbon;

class SpouseBO {

    private $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function index() {

    }

    public function getDataBySpouseId($id) {

        $data = SpouseTable::query()
        ->where('spouse_id', $id)
        ->where('f_name', '<>', '')
        ->first();

        if(!$data) return [];

        $age = '';
        if ($data->dob && $data->dob != '0000-00-00' && $data->dob != '0000-00-00 00:00:00') {
            $dob = Carbon::parse($data->dob);
            $age = $dob->age;
        }

        return [
            'id' => $data->spouse_id,
            'name' => $data->f_name . ' ' . $data->l_name,
            'firstName' => $data->f_name,
            'middleName' => $data->m_name,
            'lastName' => $data->l_name,
            'preferredName' => $data->p_name,
            'dateOfBirth' => $data->dob,
            'gender' => $data->gender,
            'sin' => $data->sin,
            'type' => $data->type,
            'beaconScore' => $data->beacon_score,
            'relation' => $data->relation,
            'mainContact' => $data->main_contact,
            'age' => $age,
            'isPep' => $data->is_pep,
            'pepDescription' => $data->pep_description,
            'signatureType' => empty($data->signature_type) ? [] : json_decode($data->signature_type),
            'signer' => $data->signer,
        ];
    }

    public function updateBySpouseId($id = null, $data = []) {
        try {
            if( $id == null ) return false;

            $spouseData = [
                "f_name" => $data['firstName'],
                "l_name" => $data['lastName'],
                "beacon_score" => isset($data['beaconScore']) ? $data['beaconScore'] : 0,
                "gender" => $data['gender'],
                "main_contact" => $data['mainContact'],
                "relation" => $data['relation'],
                "sin" => $data['socialInsuranceNumber'],
                "type" => $data['type']
            ];

            $dob = ConvertDate::convert($data['dateOfBirth']);

            if( $dob ) {
                $spouseData['dob'] = $dob;
            } else {
                $spouseData['dob'] = null;
            }

            SpouseTable::query()
            ->where('spouse_id', $id)
            ->update($spouseData);

            return true;

        } catch(\Exception $e) {
            $this->logger->error( 'SpouseBO->updateBySpouseId - Update Error', [json_encode($e)] );
            return false;
        }
    }

    public function store($applicantId, $spouseCount, $spouse) {

        try {
            if(isset($spouse['isRemoved']) && $spouse['isRemoved']) {
                SpouseTable::query()
                ->where('applicant_id', $spouse['id'])
                ->delete();

            } else {
                $spousesObj = SpouseTable::find($spouse['id']);

                if(!$spousesObj && $spouseCount == 2) {

                    $applicantTable = ApplicantTable::find($applicantId);

                    if ($applicantTable && $applicantTable->spouse2_id > 0) {
                        $spousesObj = SpouseTable::query()
                        ->where('spouse_id', $applicantTable->spouse2_id)
                        ->where('f_name', '')
                        ->where('l_name', '')
                        ->first();
                    }
                }

                if(!$spousesObj) {
                    $spousesObj = new SpouseTable();
                }

                $pepDescription = '';
                if (isset($spouse['isPep']) && $spouse['isPep'] == 'Yes') {
                    $pepDescription = $spouse['pepDescription'];
                }

                $signer = 0;
                if (isset($spouse['signer']) && isset($spouse['type'])) {
                    if ($spouse['type'] == 'Applicant' || $spouse['type'] == 'Co-Applicant') {
                        $signer = $spouse['signer'] ?? 0;
                    }
                }

                if ($signer > 0) {

                    $isPowerOfAttorney = $this->checkIsPowerOfAttorney($signer);

                    if (!$isPowerOfAttorney) {
                        $signer = 0;
                    }
                }

                $signatureType = null;
                if ((isset($spouse['signatureType']) && !empty($spouse['signatureType']))) {
                    if (isset($spouse['type']) && $spouse['type'] !== 'Do not contact' && $spouse['type'] !== 'Not a co-applicant') {
                        $signatureType = json_encode($spouse['signatureType']);
                    }
                }

                $spousesObj->f_name = ($spouse['firstName'] ?? '');
                $spousesObj->m_name = ($spouse['middleName'] ?? '');
                $spousesObj->l_name = ($spouse['lastName'] ?? '');
                $spousesObj->p_name = ($spouse['preferredName'] ?? '');
                $spousesObj->dob = ($spouse['dateOfBirth'] ?? null);
                $spousesObj->gender = ($spouse['gender'] ?? '');
                $spousesObj->sin = ($spouse['sin'] ?? '');
                $spousesObj->type = ($spouse['type'] ?? '');
                $spousesObj->beacon_score = ($spouse['beaconScore'] ?? 0);
                $spousesObj->relation = ($spouse['relation'] ?? '');
                $spousesObj->is_pep = ($spouse['isPep'] ?? '');
                $spousesObj->pep_description = $pepDescription;
                $spousesObj->main_contact = ($spouse['mainContact'] ?? 'No');
                $spousesObj->signature_type = $signatureType;
                $spousesObj->signer = $signer;
                $spousesObj->save();
            }

        } catch(\Throwable $e) {
            $this->logger->error('SpouseBO->store', [$e->getMessage(),$e->getTraceAsString()]);
            return false;
        }

        return $spousesObj->spouse_id;
    }

    public function checkIsPowerOfAttorney($signer) {

        $spouse = SpouseTable::query()
        ->where('spouse_id', $signer)
        ->where('type', 'Power of Attorney')
        ->first();

        if ($spouse) {
            return true;
        }

        return false;
    }
}
