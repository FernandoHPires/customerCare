<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\ApplicationDoc;
use App\Models\User;
use App\Amur\BO\UserBO;
use App\Amur\Utilities\ConvertDate;
use App\Amur\Utilities\Utils;
use DateTime;
use Illuminate\Support\Facades\Auth;

class TrackerBO {

    private $logger;
    private $db;
    private $errors = [];

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    private function getQuoteLender($applicationId) {
        $query = "
            SELECT 
                CASE
                    WHEN inv.investor_id = 31 THEN 'RMIF'
                    WHEN inv.investor_id = 100 THEN 'BSF'
                    WHEN inv.investor_id = 248 THEN 'MII'
                    WHEN inv.investor_id = 1971 THEN 'AB'
                    ELSE 'ACL'
                END AS lender
            FROM saved_quote_table a
            JOIN sale_investor_table inv ON a.saved_quote_id = inv.saved_quote_id
            WHERE a.application_id = ?
            AND a.disburse = 'Yes'
            ORDER BY 
                IF(inv.fm_committed = 'Yes', 1, 9),
                IF(inv.fm_committed = 'Looking', 1, 9)
            LIMIT 1
        ";

        $result = $this->db->select($query, [$applicationId]);

        if (!empty($result)) {
            return $result[0]->lender;
        }

        return null;
    }

    /**
     * Fetch tracker information.
     *
     * @return array
     */
    public function index(array $filters = []): array {
        // Base query
       $query = "
                SELECT
                ad.id AS doc_id,
                ad.application_id AS tacl_id,
                ad.doc_type,
                CASE
                    WHEN ad.support_status = 'unassigned' THEN 'Unassigned'
                    WHEN ad.support_status = 'inProgress' THEN 'In Progress'
                    WHEN ad.support_status = 'awaitingAcctg' THEN 'Awaiting Accounting'
                    WHEN ad.support_status = 'awaitingBroker' THEN 'Awaiting Broker'
                    WHEN ad.support_status = 'completed' THEN 'Completed'
                    WHEN ad.support_status = 'notCompleted' THEN 'Not Completed'
                    ELSE 'Unassigned'
                END AS support_status,
                ad.support_status AS support_status_ori,
                CASE
                    WHEN ad.accounting_status = 'unassigned' THEN 'Unassigned'
                    WHEN ad.accounting_status = 'editingTACL' THEN 'Editing TACL'
                    WHEN ad.accounting_status = 'okToFund' THEN 'OK to Fund'
                    WHEN ad.accounting_status = 'funded' THEN 'Funded'
                    WHEN ad.accounting_status = 'notFunded' THEN 'Not Funded'
                    ELSE 'Unassigned'
                END AS accounting_status,
                ad.accounting_status AS accounting_status_ori,
                ad.broker_notes,
                ad.broker_id,
                CONCAT(ub.user_fname, ' ', ub.user_lname) AS broker_name,
                ad.support_id,
                CONCAT(us.user_fname, ' ', us.user_lname) AS support_name,
                ad.accounting_id,
                CONCAT(ua.user_fname, ' ', ua.user_lname) AS accounting_name,
                ac.abbr AS origination,
                ad.create_date,
                ad.support_date,
                ad.support_complete_date,
                ad.accounting_date,
                ad.accounting_complete_date,
                ad.cancel_order,
                sq.gross AS gross_amt,
                CASE
                    WHEN inv.investor_id = 31 THEN 'RMIF'
                    WHEN inv.investor_id = 100 THEN 'BSF'
                    WHEN inv.investor_id = 248 THEN 'MII'
                    WHEN inv.investor_id = 1971 THEN 'AB'
                    ELSE 'ACL'
                END AS lender,
                mt.mortgage_id,
                mt.mortgage_code,
                mt.ab_loan,
                si.ap_inv_co,
                a_inv.abbr ap_abbr,
                si.ap_amount ap_amount,
                si.bp_inv_co,
                b_inv.abbr bp_abbr,
                si.bp_amount bp_amount, 
                si.cp_inv_co,
                c_inv.abbr cp_abbr,
                si.cp_amount cp_amount,
                (select province from property_table where application_id = ad.application_id order by idx limit 1) province
            FROM application_doc ad
            LEFT JOIN users_table ub ON ad.broker_id = ub.user_id
            LEFT JOIN users_table us ON ad.support_id = us.user_id
            LEFT JOIN users_table ua ON ad.accounting_id = ua.user_id
            LEFT JOIN application_table app ON ad.application_id = app.application_id
            LEFT JOIN alpine_companies_table ac ON app.company = ac.id
            LEFT JOIN saved_quote_table sq ON app.application_id = sq.application_id AND sq.mortgage_id = ad.mortgage_id
            LEFT JOIN mortgage_table mt ON ad.mortgage_id = mt.mortgage_id
            LEFT JOIN sale_investor_table si ON sq.saved_quote_id = si.saved_quote_id AND (si.fm_committed = 'Yes' OR si.fm_committed = 'Looking')
            LEFT JOIN investor_table inv ON si.investor_id = inv.investor_id
            LEFT JOIN alpine_companies_table a_inv on si.ap_inv_co = a_inv.id
            LEFT JOIN alpine_companies_table b_inv on si.bp_inv_co = b_inv.id
            LEFT JOIN alpine_companies_table c_inv on si.cp_inv_co = c_inv.id
        ";

        $whereConditions = [];
        $dateSelect = false; 

        if(!empty($filters['dateOrdered'])) {
            $operator = !empty($filters['dateOrderedOperator']) ? $filters['dateOrderedOperator'] : '=';

            if($filters['dateOrdered'] == (new DateTime())->format('Y-m-d') && $operator == '=') {
                $whereConditions[] = "(date(ad.create_date) = '{$filters['dateOrdered']}' OR DATE(ad.support_date) = DATE(NOW()) OR DATE(ad.accounting_date) = DATE(NOW()))";
            } else {
                $dateSelect = true;
                $whereConditions[] = "date(ad.create_date) {$operator} '{$filters['dateOrdered']}'";
            }
        }

        if(!empty($filters['supportDate'])) {
            $dateSelect = true; 
            $operator = !empty($filters['supportDateOperator']) ? $filters['supportDateOperator'] : '=';
            $whereConditions[] = "date(ad.support_date) {$operator} '{$filters['supportDate']}'";
        }

        if(!empty($filters['accountingDate'])) {
            $dateSelect = true; 
            $operator = !empty($filters['accountingDateOperator']) ? $filters['accountingDateOperator'] : '=';
            $whereConditions[] = "date(ad.accounting_date) {$operator} '{$filters['accountingDate']}'";
        }

        if(!empty($filters['selectedUser'])) {
            $whereConditions[] = "(ad.broker_id = '{$filters['selectedUser']}' or ad.support_id = '{$filters['selectedUser']}' or ad.accounting_id = '{$filters['selectedUser']}')";
        }

        if(!empty($filters['applicationId'])) {
            $whereConditions[] = "ad.application_id = " . $filters['applicationId'];
        }

        // Default condition if no filters are applied
        if(count($whereConditions) == 0) {
            $whereConditions[] = "(DATE(ad.create_date) = DATE(NOW())
                                OR (ad.support_status = 'unassigned' AND (ad.cancel_order <> 'yes' OR ad.cancel_order_user IS NULL))
                                OR DATE(ad.support_date) = DATE(NOW()) 
                                OR DATE(ad.accounting_date) = DATE(NOW()))";
        }

        
        $query .= " WHERE deleted_at is NULL AND " . implode(" AND ", $whereConditions);

        if($dateSelect) {
            $query .= " ORDER BY ad.id DESC LIMIT 10000";
        } else {
            $query .= " ORDER BY ad.id";
        }

        $result = $this->db->select($query);

        $data = [];
        foreach($result as $value) {
            $grossAmount = [];
            $lender = [];
            if($value->ab_loan === 'm_mtg') {
                if(!is_null($value->ap_inv_co)) {
                    $grossAmount[] = $value->ap_amount;
                    $lender[] = $value->ap_abbr == 'RMC' ? 'RMIF' : $value->ap_abbr;
                }
                if(!is_null($value->bp_inv_co)) {
                    $grossAmount[] = $value->bp_amount;
                    $lender[] = $value->bp_abbr == 'RMC' ? 'RMIF' : $value->bp_abbr;
                }
                if(!is_null($value->cp_inv_co)) {
                    $grossAmount[] = $value->cp_amount;
                    $lender[] = $value->cp_abbr == 'RMC' ? 'RMIF' : $value->cp_abbr;
                }
            } else {
                $grossAmount[] = $value->gross_amt;

                if(in_array($value->doc_type, ['Transmittal_Letter', 'Transfer', 'Instructions_NB'])) {
                    $quoteLender = $this->getQuoteLender($value->tacl_id);
                    $lender[] = $quoteLender ?? $value->lender;
                } else {
                    $lender[] = $value->lender;
                }
            }

            $age = ConvertDate::moment(
                new DateTime($value->create_date),
                new DateTime()
            );
            if(is_null($value->support_complete_date) || !in_array($value->doc_type,['Funding_NB', 'Funding_PB'])) {
                $accountingAge = '';
            } else {
                $accountingAge = ConvertDate::moment(
                    new DateTime($value->support_complete_date),
                    new DateTime(is_null($value->accounting_complete_date) ? 'now' : $value->accounting_complete_date)
                );
            }

            $province = $value->province;
            if($value->origination == 'ACL') {
                $province = 'BC';
            } elseif($value->origination == 'AMC') {
                $province = 'AB';
            } elseif($value->origination == 'AOL') {
                $province = 'ON';
            } elseif($value->origination == 'AQL') {
                $province = 'QC';
            }

            $data[] = [
                'id' => $value->doc_id,
                'applicationId' => $value->tacl_id,
                'origination' => $value->origination,
                'docType' => $value->doc_type,
                'brokerId' => $value->broker_id,
                'brokerNotes' => $value->broker_notes,
                'brokerName' => $value->broker_name,
                'supportId' => $value->support_id,
                'supportName' => $value->support_name,
                'supportStatus' => $value->support_status,
                'supportStatusOri' => $value->support_status_ori,
                'accountingId' => $value->accounting_id,
                'accountingName' => $value->accounting_name,
                'accountingStatus' => $value->accounting_status,
                'accountingStatusOri' => $value->accounting_status_ori,
                'createDate' => $value->create_date,
                'supportDate' => $value->support_date,
                'supportCompleteDate' => $value->support_complete_date,
                'accountingDate' => $value->accounting_date,
                'accountingCompleteDate' => $value->accounting_complete_date,
                'cancelOrder' => $value->cancel_order,
                'grossAmount' => $grossAmount,
                'lender' => $lender,
                'age' => $age,
                'accountingAge' => $accountingAge,
                'mortgageId' => $value->mortgage_id,
                'mortgageCode' => $value->mortgage_code,
                'province'=> $province,
            ];
        }

        return $data;
    }

    public function store(array $data) {

        $this->db->beginTransaction();
        try {
            // Validate input data
            if (empty($data['application_id']) || empty($data['doc_type'])) {
                $this->logger->warning('TrackerBO->store - Missing required fields', [json_encode($data)]);
                return false;
            }

            // Prepare the data for insertion
            $newDocument = [
                'application_id' => $data['application_id'],
                'mortgage_id' => $data['mortgage_id'] ?? null,
                'doc_type' => $data['doc_type'],
                'broker_id' => $data['broker_id'],
                'broker_notes' => $data['broker_notes'] ?? null,
                'support_status' => 'unassigned',
                'accounting_status' => 'unassigned',
                'create_date' => new DateTime(),
                'update_date' => new DateTime(),
                'created_by' => Auth::user()->user_id,
            ];

            $inserted = $this->db->insert('application_doc', $newDocument);

            if (!$inserted) {
                throw new \Exception('Failed to store the document in the database');
            }

            $this->logger->info("TrackerBO->store - Document stored successfully",[$data['application_id']]);

        } catch (\Throwable $e) {
            $this->db->rollback(); // Rollback on error
            $this->logger->error("TrackerBO->store - Error storing document", [$e->getMessage(), json_encode($data)]);
            $this->logger->error($e->getTraceAsString());

            $this->errors[] = 'Error saving request';
            return false;
        }

        $this->db->commit();
        return true;
    }

    public function destroy($docId): bool {
        $this->db->beginTransaction();
        try {
            $applicationDoc = ApplicationDoc::find($docId);

            if(!$applicationDoc) {
                return false;
            }

            $applicationDoc->cancel_order = 'yes';
            $applicationDoc->cancel_order_user = Auth::user()->user_id;
            $applicationDoc->update_date = new DateTime();
            $applicationDoc->save();
            
            $this->logger->info("TrackerBO->destroy - Successfully deleted document", [
                'docId' => $docId,
                'userId' => Auth::user()->user_id,
            ]);
    
        } catch (\Throwable $e) {
            $this->db->rollback();
            $this->logger->error("TrackerBO->destroy - Error canceling document", [$docId, $e->getMessage()]);
            return false;
        }

        $this->db->commit();
        return true;
    }

    public function updateAccountingStatus($docId, $newStatus, $accountingId) {
        $this->db->beginTransaction();
        try {
            $applicationDoc = ApplicationDoc::find($docId);
            if(!$applicationDoc) {
                $this->logger->error("TrackerBO->updateAccountingStatus - Document not found for ID", [$docId]);
                return false;
            }

            $updateFields = [
                'accounting_status' => $newStatus,
                'accounting_id' => $accountingId,
                'update_date' => new DateTime(),
            ];

            if(is_null($applicationDoc->accounting_date)) {
                $updateFields['accounting_date'] = new DateTime();
            }

            if($newStatus == 'funded') {
                $updateFields['accounting_complete_date'] = new DateTime();
            }
        
            // Perform the update and check rows affected
            $rowsAffected = $this->db->update('application_doc', $updateFields, ['id' => $docId]);
        
            if ($rowsAffected === 0) {
                $this->logger->error("TrackerBO->updateAccountingStatus - No rows updated for document ID", [
                    'docId' => $docId,
                    'newStatus' => $newStatus,
                    'accountingId' => $accountingId,
                    'updateFields' => $updateFields
                ]);
                throw new \Exception("No rows were updated for document ID: $docId");
            }
        
            $this->logger->info("TrackerBO->updateAccountingStatus - Accounting status updated successfully", [
                'docId' => $docId,
                'newStatus' => $newStatus,
                'accountingId' => $accountingId,
                'updateFields' => $updateFields
            ]);
        
            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            $this->logger->error("TrackerBO->updateAccountingStatus - Error updating accounting status", [
                'docId' => $docId,
                'newStatus' => $newStatus,
                'accountingId' => $accountingId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function updateSupportStatus($docId, $newStatus, $supportId, $override) {
        $this->db->beginTransaction();
        try {
            // Fetch existing support ID for comparison
            $applicationDoc = ApplicationDoc::find($docId);
            if(!$applicationDoc) {
                $this->logger->error("TrackerBO->updateSupportStatus - Document not found for ID", [$docId]);
                return false;
            }
            
            $existingSupportId = $applicationDoc->support_id ?? null;

            // Check if takeover is happening
            $isTakeover = ($existingSupportId !== null && $existingSupportId != $supportId);
            if($isTakeover && !$override) {
                $result = $this->db->select(
                    'SELECT CONCAT(user_fname, " ", user_lname) as name FROM users_table WHERE user_id = ?',
                    [$existingSupportId]
                );

                $existingSupportName = $result[0]->name ?? 'Unknown';

                $this->logger->info("TrackerBO->updateSupportStatus - Takeover detected, override not provided", [
                    'docId' => $docId,
                    'newStatus' => $newStatus,
                    'supportId' => $supportId,
                    'existingSupportId' => $existingSupportId
                ]);

                return [
                    'isTakeover' => $isTakeover,
                    'existingSupportName' => $existingSupportName,
                ];
            }

            $updateFields = [
                'support_status' => $newStatus,
                'support_id' => $supportId,
                'update_date' => new DateTime(),
            ];

            if(is_null($applicationDoc->support_date)) {
                $updateFields['support_date'] = new DateTime();
            }

            // Set support_complete_date only if status is 'completed'
            if($newStatus == 'completed') {
                $updateFields['support_complete_date'] = new DateTime();
            }

            // Perform the update and check rows affected
            $rowsAffected = $this->db->update('application_doc', $updateFields, ['id' => $docId]);

            if ($rowsAffected === 0) {
                $this->logger->error("TrackerBO->updateSupportStatus - No rows updated for document ID", [
                    'docId' => $docId,
                    'newStatus' => $newStatus,
                    'supportId' => $supportId,
                    'updateFields' => $updateFields
                ]);
                throw new \Exception("No rows were updated for document ID: $docId");
            }

            // Check if email needs to be sent
            if(env('APP_ENV') === 'production' && in_array(strtolower($newStatus), ['completed', 'notcompleted'])) {
                $applicationDocResults = $this->db->select(
                    'SELECT * FROM application_doc WHERE id = ?',
                    [$docId]
                );

                if(empty($applicationDocResults)) {
                    $this->logger->error('TrackerBO->updateSupportStatus - Document not found for ID', [$docId]);
                    throw new \Exception("Document not found for ID: $docId");
                }

                $applicationDoc = $applicationDocResults[0];

                $brokerResults = $this->db->select(
                    'SELECT user_fname, user_lname, user_email FROM users_table WHERE user_id = ?',
                    [$applicationDoc->broker_id]
                );

                if(empty($brokerResults) || empty($brokerResults[0]->user_email)) {
                    $this->logger->error('TrackerBO->updateSupportStatus - Broker email not found', [$applicationDoc->broker_id]);
                    throw new \Exception("Broker email not found for broker_id: {$applicationDoc->broker_id}");
                }

                $brokerName = "{$brokerResults[0]->user_fname} {$brokerResults[0]->user_lname}";
                $brokerEmail = $brokerResults[0]->user_email;

                $supportUserResults = $this->db->select(
                    'SELECT user_fname, user_lname FROM users_table WHERE user_id = ?',
                    [$applicationDoc->support_id]
                );

                $supportName = !empty($supportUserResults)
                    ? "{$supportUserResults[0]->user_fname} {$supportUserResults[0]->user_lname}"
                    : "N/A";
                
                $toAddresses = [$brokerEmail];
                $subject = "TACL# {$applicationDoc->application_id}, {$applicationDoc->doc_type} documents {$newStatus}";
                $body = "Doc#: {$applicationDoc->id}<br>Broker: {$brokerName}<br>Support: {$supportName}<br>";

                $this->logger->info("TrackerBO->updateSupportStatus - Sending email", [
                    'toAddresses' => $toAddresses,
                    'subject' => $subject,
                    'body' => $body,
                ]);
                
                Utils::sendEmail($toAddresses, $subject, $body);
            }

            $this->logger->info("TrackerBO->updateSupportStatus - Support status updated successfully", [
                'docId' => $docId,
                'newStatus' => $newStatus,
                'supportId' => $supportId,
                'updateFields' => $updateFields
            ]);

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {
            $this->db->rollback();
            $this->logger->error("TrackerBO->updateSupportStatus - Error updating support status", [
                'docId' => $docId,
                'newStatus' => $newStatus,
                'supportId' => $supportId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getDocumentTypes($userId) {
        $user = User::find($userId);
        if(!$user) {
            return [];
        }

        $condition = 'where 1 = 1';
        if($user->doctracker_view == 'Broker') {
            if($user->default_company_id == 701) {
                $condition .= ' and company_group like "%SQC%"';
            } else {
                $condition .= ' and company_group like "%ACL%"';
            }
        }

        $sql = "select Name from application_doc_type
                       $condition
              order by Name";
        $res = $this->db->select($sql);

        $data = array();
        foreach($res as $key => $value) {
            $name = str_replace('_', ' ', $value->Name);
            $data[] = [
                'id' => $value->Name,
                'name' => $name,
            ];
        }

        return $data;
    }

    public function getAllUsers(): array {
        $userBO = new UserBO($this->logger, $this->db);
        $users = $userBO->index();

        // Filter out users who do not have a trackerView
        $filteredUsers = array_filter($users, function ($user) {
            return !empty($user['trackerView']); // Only include users with a non-empty trackerView
        });

        // Format the users for the frontend
        $data = array_map(function ($user) {
            return [
                'id' => $user['id'],
                'name' => "{$user['firstName']} {$user['lastName']} ({$user['trackerView']})",
            ];
        }, $filteredUsers);

        return $data;
    }
    
    public function generateReport($startDate, $endDate, $companyId = null) {
        try {
            // SQL query to fetch brokers and count of each doc_type
            $query = "SELECT 
                    CONCAT(ub.user_fname, ' ', ub.user_lname) AS broker_name,
                    ub.default_company_id AS company_id, 
                    SUM(CASE WHEN ad.doc_type = 'Title_Docs' THEN 1 ELSE 0 END) AS title_docs_count,
                    SUM(CASE WHEN ad.doc_type = 'Transmittal_Letter' THEN 1 ELSE 0 END) AS transmittal_letter_count,
                    SUM(CASE WHEN ad.doc_type = 'Instructions_NB' THEN 1 ELSE 0 END) AS instructions_nb_count,
                    SUM(CASE WHEN ad.doc_type = 'Instructions_PB' THEN 1 ELSE 0 END) AS instructions_pb_count,
                    SUM(CASE WHEN ad.doc_type = 'Transfer' THEN 1 ELSE 0 END) AS transfer_count,
                    SUM(CASE WHEN ad.doc_type = 'Funding_NB' THEN 1 ELSE 0 END) AS funding_nb_count,
                    SUM(CASE WHEN ad.doc_type = 'Funding_PB' THEN 1 ELSE 0 END) AS funding_pb_count,
                    SUM(CASE WHEN ad.doc_type = 'Credit_Bureau' THEN 1 ELSE 0 END) AS credit_bureau_count,
                    SUM(CASE WHEN ad.doc_type = 'Initial_Docs_NB' THEN 1 ELSE 0 END) AS initial_docs_nb_count,
                    SUM(CASE WHEN ad.doc_type = 'Initial_Docs_PB' THEN 1 ELSE 0 END) AS initial_docs_pb_count,
                    SUM(CASE WHEN ad.doc_type = 'Suitability' THEN 1 ELSE 0 END) AS suitability_count,
                    SUM(CASE WHEN ad.doc_type = 'Disbursement_PA' THEN 1 ELSE 0 END) AS disbursement_pa_count
                FROM application_doc ad
                LEFT JOIN users_table ub ON ad.broker_id = ub.user_id
                WHERE ad.deleted_at IS NULL
                AND DATE(ad.support_date) BETWEEN :startDate AND :endDate
                AND (
                    (ad.doc_type LIKE 'Funding%' AND ad.support_status = 'completed' AND ad.accounting_status = 'funded')
                    OR
                    (ad.doc_type NOT LIKE 'Funding%' AND ad.support_status = 'completed')
                )
                AND (ad.cancel_order IS NULL OR ad.cancel_order <> 'yes')
                GROUP BY ad.broker_id, ub.user_fname, ub.user_lname, ub.default_company_id
                ORDER BY broker_name ASC;";
    
            // Bind parameters
            $params = [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ];
    
            // Execute the query
            $result = $this->db->select($query, $params);
    
            // Format and return the result
            $data = [];
            foreach ($result as $row) {
                $data[] = [
                    'broker' => $row->broker_name,
                    'companyId' => $row->company_id,
                    'titleDocs' => $row->title_docs_count,
                    'transmittalLetter' => $row->transmittal_letter_count,
                    'instructionNB' => $row->instructions_nb_count,
                    'instructionPB' => $row->instructions_pb_count,
                    'transfer' => $row->transfer_count,
                    'fundingNB' => $row->funding_nb_count,
                    'fundingPB' => $row->funding_pb_count,
                    'creditBureau' => $row->credit_bureau_count,
                    'initialDocsNB' => $row->initial_docs_nb_count,
                    'initialDocsPB' => $row->initial_docs_pb_count,
                    'suitability' => $row->suitability_count,
                    'disbursementPA' => $row->disbursement_pa_count
                ];
            }

            return $data;
        } catch (\Exception $e) {
            $this->logger->error("TrackerBO->generateReport - Error generating report", [$e->getMessage()]);
            return [];
        }
    } 

    public function getSupportDocsPerMonth($year, $month) {
        try {
            $query = "
                SELECT
                  (SELECT CONCAT(u.user_fname, ' ', u.user_lname)
                   FROM users_table u
                   WHERE u.user_id = ad.support_id) AS support,
                  ad.support_id,
                  ad.doc_type,
                  ad.support_date,
                  MONTH(ad.support_date) AS month_num,
                  YEAR(ad.support_date) AS year_num,
                  DAY(ad.support_date) AS day_num,
                  COUNT(*) AS count
                FROM application_doc ad
                WHERE ad.deleted_at IS NULL
                  AND
                  (
                    (ad.doc_type LIKE 'funding%' AND ad.support_status = 'completed' AND ad.accounting_status = 'funded')
                    OR (ad.doc_type NOT LIKE 'funding%' AND ad.support_status = 'completed')
                  )
                  AND MONTH(ad.support_date) = :month
                  AND YEAR(ad.support_date) = :year
                  AND (ad.cancel_order IS NULL OR ad.cancel_order <> 'yes')
                GROUP BY support, support_id, doc_type, year_num, month_num, day_num
                ORDER BY support, day_num
            ";
    
            $results = $this->db->select($query, [
                'month' => $month,
                'year' => $year
            ]);
    
            $data = [];
            foreach ($results as $row) {
                $data[] = [
                    'support'     => $row->support,
                    'supportId'  => $row->support_id,
                    'docType'    => $row->doc_type,
                    'supportDate'=> $row->support_date,
                    'dayNum'      => $row->day_num,
                    'monthNum'    => $row->month_num,
                    'yearNum'     => $row->year_num,
                    'count'         => $row->count,
                ];
            }
    
            return $data;
        } catch (\Exception $e) {
            $this->logger->error("TrackerBO->getSupportDocsPerMonth - Error", [$e->getMessage()]);
            return [];
        }
    }

    public function getSupportFundingsPerWeek() {
        try {
            $end_date = new DateTime();
            $start_date = (clone $end_date)->modify('-9 weeks') // 9 weeks ago
                ->modify('monday this week') // force start of week
                ->format('Y-m-d');

            $query = "SELECT
                        (SELECT CONCAT(u.user_fname, ' ', u.user_lname)
                        FROM users_table u
                        WHERE u.user_id = ad.support_id) AS support,
                        ad.support_id,
                        'Funding' AS doc_type,
                        WEEKOFYEAR(support_date) AS week_num,
                        DATE_ADD(support_date, INTERVAL - WEEKDAY(support_date) DAY) AS monday,
                        MONTH(support_date) AS month_num,
                        YEAR(support_date) AS year_num,
                        COUNT(*) AS count
                    FROM application_doc ad
                    WHERE ad.deleted_at IS NULL
                        AND doc_type LIKE 'Funding%'
                        AND support_status = 'completed'
                        AND accounting_status = 'funded'
                        AND (ad.cancel_order IS NULL OR ad.cancel_order <> 'yes')
                        AND support_date >= :start_date
                    GROUP BY ad.support_id, year_num, week_num
                    ORDER BY support, year_num, week_num";

            $results = $this->db->select($query, ['start_date' => $start_date]);

            $start_date_formatted = $end_date->format('Y-m-d');

            $data = [];
            foreach ($results as $row) {
                $data[] = [
                    'support'     => $row->support,
                    'supportId'  => $row->support_id,
                    'docType'    => $row->doc_type,
                    'weekNum'    => $row->week_num,
                    'monday'      => $row->monday,
                    'monthNum'   => $row->month_num,
                    'yearNum'    => $row->year_num,
                    'count'         => $row->count,
                    'startDate'  => $start_date_formatted,
                ];
            }

            return $data;

        } catch (\Exception $e) {
            $this->logger->error("TrackerBO->getSupportFundingsPerWeek - Error", [$e->getMessage()]);
            return [];
        }
    }

    public function getReportPerDay($year, $month) {
       try {
            $query = "
                SELECT
                  (SELECT CONCAT(u.user_fname, ' ', u.user_lname)
                   FROM users_table u
                   WHERE u.user_id = ad.support_id) AS support,
                  ad.support_id,
                  ad.doc_type,
                  ad.support_date,
                  MONTH(ad.support_date) AS month_num,
                  YEAR(ad.support_date) AS year_num,
                  DAY(ad.support_date) AS day_num,
                  COUNT(*) AS count
                FROM application_doc ad
                WHERE ad.deleted_at IS NULL 
                  AND
                  (
                    (ad.doc_type LIKE 'funding%' AND ad.support_status = 'completed' AND ad.accounting_status = 'funded')
                  )
                  AND MONTH(ad.support_date) = :month
                  AND YEAR(ad.support_date) = :year
                  AND (ad.cancel_order IS NULL OR ad.cancel_order <> 'yes')
                GROUP BY support, support_id, doc_type, year_num, month_num, day_num
                ORDER BY support, day_num
            ";
    
            $results = $this->db->select($query, [
                'month' => $month,
                'year' => $year
            ]);
    
            $data = [];
            foreach ($results as $row) {
                $data[] = [
                    'support'     => $row->support,
                    'supportId'  => $row->support_id,
                    'docType'    => $row->doc_type,
                    'supportDate'=> $row->support_date,
                    'dayNum'      => $row->day_num,
                    'monthNum'    => $row->month_num,
                    'yearNum'     => $row->year_num,
                    'count'         => $row->count,
                ];
            }
    
            return $data;
        } catch (\Exception $e) {
            $this->logger->error("TrackerBO->getSupportDocsPerMonth - Error", [$e->getMessage()]);
            return [];
        }
    }

    public function updateNotes($docId, $brokerNotes) {
        $this->db->beginTransaction(); // Start transaction
    
        try {
            // Check if the document exists
            $document = $this->db->select("SELECT * FROM application_doc WHERE id = ?", [$docId]);
    
            if (empty($document)) {
                throw new \Exception("TrackerBO->updateBrokerNotes - Document not found for ID: $docId");
            }
    
            // Update the broker notes
            $updateFields = [
                'broker_notes' => $brokerNotes,
                'update_date' => new DateTime(),
            ];
    
            $rowsAffected = $this->db->update('application_doc', $updateFields, ['id' => $docId]);
    
            if ($rowsAffected === 0) {
                throw new \Exception("TrackerBO->updateBrokerNotes - No changes were made to document ID: $docId");
            }
    
            $this->db->commit(); // Commit transaction
    
            // Log the update
            $this->logger->info("TrackerBO->updateBrokerNotes - Updated broker notes", [
                'docId' => $docId,
                'brokerNotes' => $brokerNotes,
                'userId' => Auth::user()->user_id,
            ]);
    
            return true;
        } catch (\Exception $e) {
            $this->db->rollback(); // Rollback on failure
            $this->logger->error("TrackerBO->updateBrokerNotes - Error updating broker notes", [
                'docId' => $docId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getErrors(): array {
        return $this->errors;
    }
}
