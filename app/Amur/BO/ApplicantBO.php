<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Amur\Bean\SalesforceIntegration;
use App\Models\ApplicantTable;
use App\Models\SpouseTable;
use App\Models\ApplicantContactsTable;
use Illuminate\Support\Facades\Auth;

class ApplicantBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getDataByApplicationId($id) {

        $appObj = ApplicantTable::query()
        ->where('application_id', $id)
        ->orderBy('applicant_id', 'asc')
        ->get();

        $applicants = array();
        foreach($appObj as $key => $value) {

            $spouseBO = new SpouseBO($this->logger);
            $spouse1 = $spouseBO->getDataBySpouseId($value->spouse1_id);
            $spouse2 = $spouseBO->getDataBySpouseId($value->spouse2_id);

            $spouses = array();

            $name1 = ''; 
            $name2 = ''; 

            if($spouse1) {
                $spouses[] = $spouse1;
                $name1 = isset($spouse1['preferredName']) && !empty($spouse1['preferredName']) ? $spouse1['preferredName'] : $spouse1['firstName'];
            }else {
                $spouses[] = [
                    'id' => 0,
                    'name' => '',
                    'firstName' => '',
                    'middleName' => '',
                    'lastName' => '',
                    'preferredName' => '',
                    'dateOfBirth' => null,
                    'gender' => 'F',
                    'sin' => '',
                    'type' => 'Applicant',
                    'beaconScore' => 0,
                    'relation' => '',
                    'mainContact' => 'No',
                    'age' => '',
                    'isPep' => '',
                    'pepDescription' => '',
                    'signatureType' => [],
                    'signer' => 0,
                ];
            }
            if($spouse2) {
                $spouses[] = $spouse2;
                $name2 = isset($spouse2['preferredName']) && !empty($spouse2['preferredName']) ? $spouse2['preferredName'] : $spouse2['firstName'];
            }            

            $contacts = $this->getContactsByApplicantId($value->applicant_id);

            $applicants[] = [
                'id' => $value->applicant_id,
                'creditBureauRec' => $value->credit_bureau_recvd,
                'maritalStatus' => $value->marital_status,
                'yearsOfMaritalStatus' => $value->marital_how_long,
                'childrenCount' => $value->children,
                'childrenAges' => $value->ages,
                'homePhone' => $value->home_fax,
                'homeMobile' => $value->home_phone,
                'workPhone' => $value->work_phone,
                'email' => $value->home_email,
                'isRemoved' => false,
                'spouses' => $spouses,
                'contacts' => $contacts,
                'contactOptions' => $this->getContactOptions($name1, $name2)
            ];
        }

        return $applicants;
    }

    public function getContactOptions($name1, $name2) {
        
        $ctOptions = array();

        $query = "select * from contact_types_table where id>100 or (id<11 and id<>3)  order by single";
        $result = $this->db->select($query);

        $q = 0;
        foreach ($result as $row) {
            $ctOptions[$q] = array();
            $ctOptions[$q][0] = $row->name;
            $ctOptions[$q][1] = $row->single;
            $q++;
        }

        $options = $this->getOptions($name1, $name2, $ctOptions);

        return $options;

    }

    public function getContactsByApplicantId($id) {

        $contacts = array();

        $contactsTable = ApplicantContactsTable::query()
        ->where('applicant_id', $id)
        ->get();        

        foreach($contactsTable as $row) {
            $contacts[] = [
                'id' => $row->contact_id,
                'type' => $row->type,
                'info' => $row->info
            ];
        }

        return $contacts;
    }

    public function getOptions($n1, $n2, $list) {

        $options = array();

        $j = 1;
        for($i = 0; $i < sizeof($list); $i++) {
            if($list[$i][1] == 'N') {
                if(strcmp($n1, "") != 0) {
                    $options[$j++] = [
                        'id' => $n1."'s ".$list[$i][0],
                        'name' => $n1."'s ".$list[$i][0]
                    ];
                }

                if(strcmp($n2, "") != 0) {
                    $options[$j++] = [
                        'id' => $n2."'s ".$list[$i][0],
                        'name' => $n2."'s ".$list[$i][0]
                    ];
                }
            }
            else {
                $options[$j++] = [
                    'id' => $list[$i][0],
                    'name' => $list[$i][0]
                ];
            }
        }

        return $options;
    }

    /*public function updateByApplicantId($id = null, $personalData = [], $contactData) {
        try {
            if( $id == null ) return false;
            $creditBureau = ConvertDate::convert($personalData['creditBureauRec']);

            // data are mixed from personal info and contact info fields in the form
            $fields = [
                "children" => $personalData['childrenCount'],
                "ages" => $personalData['childrenAges'],
                "marital_status" => $personalData['maritalStatus'],
                "marital_how_long" => $personalData['yearsOfMaritalStatus'],
                "home_email" => isset($contactData['email']) ? $contactData['email'] : "",
                "home_fax" => isset($contactData['homePhone']) ? $contactData['homePhone'] : "",
                "home_phone" => isset($contactData['homeMobile']) ? $contactData['homeMobile'] : ""
            ];

            if( $creditBureau ) {
                $fields["credit_bureau_recvd"] = $creditBureau;
            } else {
                $fields["credit_bureau_recvd"] = null;
            }

            ApplicantTable::query()
            ->where("applicant_id", $id)
            ->update($fields);

            return true;

        } catch(\Exception $e) {
            $this->logger->error( 'ApplicantBO->updateByApplicantId - Update Error', [json_encode($e)] );
            return false;
        }
    }*/

    public function getApplicantsSF($opportunityId, $userId) {

        $applicationId = null;
        $sfi = new SalesforceIntegration($this->db, $this->logger);
        if($sfi->getBySalesforceId('Opportunity', $opportunityId)) {
            $applicationId = $sfi->getObjectId();
        }

        if(is_null($applicationId) || empty($applicationId)) return false;

        $applicants = $this->getDataByApplicationId($applicationId);

        $spouses = array();
        foreach($applicants as $key => $value) {
            if(isset($value['spouses'][0]) && !empty($value['spouses'][0]['lastName'])) {
                $spouses[] = $value['spouses'][0];
                $applicants[$key]['spouse1'] = $value['spouses'][0]['id'];
            } else {
                $applicants[$key]['spouse1'] = 0;
            }

            if(isset($value['spouses'][1]) && !empty($value['spouses'][1]['lastName'])) {
                $spouses[] = $value['spouses'][1];
                $applicants[$key]['spouse2'] = $value['spouses'][1]['id'];
            } else {
                $applicants[$key]['spouse2'] = 0;
            }
        }
        
        return [
            'applicants' => $applicants,
            'spouses' => $spouses
        ];
    }

    public function updateApplicantsSF($opportunityId, $userId, $applicants) {
        $applicationId = null;
        $sfi = new SalesforceIntegration($this->db, $this->logger);
        if($sfi->getBySalesforceId('Opportunity', $opportunityId)) {
            $applicationId = $sfi->getObjectId();
        }

        if(is_null($applicationId) || empty($applicationId)) return false;

        foreach($applicants as $key => $value) {
            if($value['spouse1'] == 0 && $value['spouse2'] == 0) {
                $this->logger->error('ApplicantBO->updateApplicantsSF - Both spouses are empty',[$opportunityId]);
                return false;

            } elseif($value['spouse1'] == 0 && $value['spouse2'] != 0) {
                $applicants[$key]['spouse1'] = $value['spouse2'];
                $applicants[$key]['spouse2'] = 0;
            }
        }

        $res = $this->getApplicantsSF($opportunityId, $userId);
        $oldApplicants = $res['applicants'];
        $oldSpouses = $res['spouses'];

        $this->logger->debug('ApplicantBO->updateApplicantsSF',[json_encode($oldApplicants)]);

        foreach($applicants as $key => $value) {

            if(isset($oldApplicants[$key])) {

                if($value['spouse1'] == $oldApplicants[$key]['spouse1'] && $value['spouse2'] == $oldApplicants[$key]['spouse2']) {
                    //no action

                } elseif($value['spouse1'] == $oldApplicants[$key]['spouse2'] && $value['spouse2'] == $oldApplicants[$key]['spouse1']) {
                    //swap spouses within the same applicant
                    $applicant = ApplicantTable::find($oldApplicants[$key]['id']);
                    $applicant->spouse1_id = $value['spouse1'];
                    $applicant->spouse2_id = $value['spouse2'];
                    $applicant->save();

                } else {
                    if($value['spouse1'] != $oldApplicants[$key]['spouse1']) {
                        //update spouse1
                        $applicantData = $this->findApplicantBySpouseId($value['spouse1'], $oldApplicants);

                        $applicant = ApplicantTable::find($oldApplicants[$key]['id']);
                        $applicant->spouse1_id = $value['spouse1'];
                        $applicant->home_phone = $applicantData['homeMobile'];
                        $applicant->home_fax = $applicantData['homePhone'];
                        $applicant->home_email = $applicantData['email'];
                        $applicant->work_phone = $applicantData['workPhone'];
                        $applicant->marital_status = $applicantData['maritalStatus'];
                        $applicant->marital_how_long = $applicantData['yearsOfMaritalStatus'];
                        $applicant->children = $applicantData['childrenCount'];
                        $applicant->ages = $applicantData['childrenAges'];
                        $applicant->credit_bureau_recvd = $applicantData['creditBureauRec'];
                        $applicant->save();
                    }

                    if($value['spouse2'] != $oldApplicants[$key]['spouse2']) {
                        //update spouse2

                        $spouse2Id = $value['spouse2'];
                        if($spouse2Id == 0) {
                            $spouse = new SpouseTable();
                            $spouse->l_name = '';
                            $spouse->f_name = '';
                            $spouse->m_name = '';
                            $spouse->p_name = '';
                            $spouse->dob = '0000-00-00';
                            $spouse->sin = '';
                            $spouse->type = 'Co-Applicant';
                            $spouse->main_contact = 'No';
                            $spouse->relation = '';
                            $spouse->gender = 'M';
                            $spouse->beacon_score = 0;
                            $spouse->tu_consent = null;
                            $spouse->tu_score = null;
                            $spouse->is_portal_user = 'No';
                            $spouse->save();

                            $spouse2Id = $spouse->spouse_id;
                        }

                        $applicant = ApplicantTable::find($oldApplicants[$key]['id']);
                        $applicant->spouse2_id = $spouse2Id;
                        $applicant->save();
                    }
                }
            } else {
                //insert new applicant
                $spouse2Id = $value['spouse2'];
                if($spouse2Id == 0) {
                    $spouse = new SpouseTable();
                    $spouse->l_name = '';
                    $spouse->f_name = '';
                    $spouse->m_name = '';
                    $spouse->p_name = '';
                    $spouse->dob = '0000-00-00';
                    $spouse->sin = '';
                    $spouse->type = 'Co-Applicant';
                    $spouse->main_contact = 'No';
                    $spouse->relation = '';
                    $spouse->gender = 'M';
                    $spouse->beacon_score = 0;
                    $spouse->tu_consent = null;
                    $spouse->tu_score = null;
                    $spouse->is_portal_user = 'No';
                    $spouse->save();

                    $spouse2Id = $spouse->spouse_id;
                }

                $applicant = new ApplicantTable();
                $applicant->application_id = $applicationId;
                $applicant->spouse1_id = $value['spouse1'];
                $applicant->spouse2_id = $spouse2Id;
                $applicant->home_phone = '';
                $applicant->home_fax = '';
                $applicant->home_email = '';
                $applicant->work_phone = '';
                $applicant->marital_status = '';
                $applicant->marital_how_long = '';
                $applicant->children = '';
                $applicant->ages = '';
                $applicant->credit_bureau_recvd = '0000-00-00';
                $applicant->save();
            }
        }
    }

    public function findApplicantBySpouseId($spouseId, $applicants) {
        foreach($applicants as $value) {
            if($value['spouse1'] == $spouseId || $value['spouse2'] == $spouseId) {
                return $value;
            }
        }

        return false;
    }

    public function store($applicants, $applicationId) {
        $this->logger->info('ApplicantBO->store',[$applicationId]);

        $this->db->beginTransaction();
        try {
            foreach($applicants as $applicant) {

                if(isset($applicant['isRemoved']) && $applicant['isRemoved']) {
                    ApplicantTable::query()
                    ->where('applicant_id', $applicant['id'])
                    ->delete();

                    foreach($applicant['spouses'] as $data) {                        
                        SpouseTable::query()
                        ->where('spouse_id', $data['id'])
                        ->delete();                        
                    }

                    foreach($applicant['contacts'] as $data) {
                        ApplicantContactsTable::query()
                        ->where('contact_id', $data['id'])
                        ->delete();                       
                    }

                } else {
                    $applicantsObj = ApplicantTable::find($applicant['id']);

                    if(!$applicantsObj) {
                        $applicantsObj = new ApplicantTable();
                        $applicantsObj->application_id = $applicationId;
                    }

                    $applicantsObj->home_fax = ($applicant['homePhone'] ?? '');
                    $applicantsObj->home_phone = ($applicant['homeMobile'] ?? '');
                    $applicantsObj->home_email = ($applicant['email'] ?? '');
                    $applicantsObj->marital_status = ($applicant['maritalStatus'] ?? '');
                    $applicantsObj->credit_bureau_recvd = ($applicant['creditBureauRec'] ?? '0000-00-00');
                    $applicantsObj->marital_how_long = ($applicant['yearsOfMaritalStatus'] ?? 0);
                    $applicantsObj->children = ($applicant['childrenCount'] ?? '');
                    $applicantsObj->ages = ($applicant['childrenAges'] ?? '');

                    if($applicantsObj->save()) {
                        $applicantId = $applicantsObj->applicant_id;
                        $spouse1Id   = $applicantsObj->spouse1_id;
                        $spouse2Id   = $applicantsObj->spouse2_id;
                        $spouseCount = 0;

                        foreach($applicant['spouses'] as $data) {

                            $spouseCount++;
                            $spouseBO = new SpouseBO($this->logger, $this->db);
                            $spouseId = $spouseBO->store($applicantId, $spouseCount, $data);
    
                            if($spouseCount == 1) {
                                $spouse1Id = $spouseId;
                            } else {
                                $spouse2Id = $spouseId;
                            }
                        }

                        $applicantaTable = ApplicantTable::find($applicantId);
                        $applicantaTable->spouse1_id = $spouse1Id;
                        $applicantaTable->spouse2_id = $spouse2Id;
                        $applicantaTable->save();

                        if($spouseCount == 1) {
                            $this->checkSpouseTable($applicantId);
                        }
                        
                        $this->saveContacts($applicant['contacts'], $applicantId);
                    }
                }
            }

            foreach($applicants as $key => $applicant) {
                foreach($applicant['spouses'] as $data) {

                    $signer = $data['signer'] ?? 0;

                    if ($signer > 0) {

                        $spouseBO = new SpouseBO($this->logger, $this->db);
                        $isPowerOfAttorney = $spouseBO->checkIsPowerOfAttorney($signer);

                        if (!$isPowerOfAttorney) {
                            $spousesObj = SpouseTable::find($data['id']);
                            if ($spousesObj) {
                                $spousesObj->signer = 0;
                                $spousesObj->save();
                            }
                        }
                    }
                }
            }


        } catch(\Throwable $e) {
            $this->logger->error('ApplicantBO->store', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;
    }

    public function saveContacts($contacts, $applicantId) {
        try {
            foreach($contacts as $contact) {
                
                if (isset($contact['isRemoved']) && $contact['isRemoved']) {
                    ApplicantContactsTable::query()
                    ->where('contact_id', $contact['id'])
                    ->delete();
                } else {
                    $contactsObj = ApplicantContactsTable::find($contact['id']);
    
                    if(!$contactsObj) {
                        $contactsObj = new ApplicantContactsTable();
                        $contactsObj->applicant_id = $applicantId;
                    }
        
                    $contactsObj->type = $contact['type'] ?? '';
                    $contactsObj->info = $contact['info'] ?? '';
                    $contactsObj->save();
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('ApplicantBO->saveContacts', [$e->getMessage(),$e->getTraceAsString()]);
        }
    }

    public function checkSpouseTable($applicantId) {
        $applicantTable = ApplicantTable::find($applicantId);

        if($applicantTable && $applicantTable->spouse2_id == 0) {
            $spousesObj = new SpouseTable();        
            $spousesObj->f_name = '';
            $spousesObj->m_name = '';
            $spousesObj->l_name = '';
            $spousesObj->p_name = '';
            $spousesObj->dob = '';
            $spousesObj->gender = '';
            $spousesObj->sin = '';
            $spousesObj->type = '';
            $spousesObj->beacon_score = '';
            $spousesObj->relation = '';
            $spousesObj->is_pep = '';
            $spousesObj->pep_description = '';
            $spousesObj->main_contact = '';
            $spousesObj->save();

            $applicantTable->spouse2_id = $spousesObj->spouse_id;
            $applicantTable->save();
        }
    }


    public function getSigners($applicationId) {

        $query = "select a.spouse_id, a.f_name, a.m_name, a.l_name, a.type  
                  from spouse_table a
                  join applicant_table b on b.spouse1_id = a.spouse_id or b.spouse2_id = a.spouse_id 
                  where b.application_id = ?
                  order by applicant_id";
        $spouseTable = $this->db->select($query, [$applicationId]);

        $signers = array();

        $signers[] = [
            'id' => 0,
            'name' => 'None',
        ];

        foreach ($spouseTable as $spouse) {

            $name = $spouse->f_name;

            if (!empty($spouse->m_name)) {
                $name .= ' ' . $spouse->m_name;
            }

            $name .= ' ' . $spouse->l_name;

            $name = trim($name);

            if ($spouse->type == 'Power of Attorney') {
                $signers[] = [
                    'id' => $spouse->spouse_id,
                    'name' => $name,
                ];
            }
        }

        return $signers;
    }

}
