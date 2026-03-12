<?php

namespace App\Amur\BO;

use DateTime;
use App\Models\NsfFee;
use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\UsersTable;
use App\Amur\Bean\Response;
use App\Amur\BO\MortgageBO;
use App\Amur\BO\SalesforceBO;
use App\Amur\Bean\SalesforceIntegration;
use App\Amur\Utilities\Utils;
use App\Models\PbPool;
use App\Models\PapBank;
use App\Models\NotesTable;
use App\Models\PbNewAppLog;
use App\Models\SourcesTable;
use App\Models\BranchesTable;
use App\Models\MortgageTable;
use App\Models\PropertyTable;
use App\Models\MortgageNsfFee;
use App\Models\PapInstitution;
use App\Models\SavedQuoteTable;
use App\Models\PositionsTable;
use App\Models\LawyerFileTable;
use App\Models\ApplicationTable;
use App\Models\PeriodLocksTable;
use App\Models\CorporationTable;
use App\Models\SaleInvestorTable;
use App\Models\AlpineCompaniesTable;
use App\Models\MortgagePaymentsTable;
use App\Models\PropertyMortgagesTable;
use App\Models\MortgagePropertiesTable;
use App\Models\MortgageMortgagorsTable;
use App\Models\MortgageInterestRatesTable;
use App\Models\SavedQuoteMissingChequesTable;
use App\Models\MortgageInvestorTrackingTable;
use App\Models\MortgageInvestorTrackingInvestorsTable;
use App\Models\StatusInfo;
use Illuminate\Support\Facades\Auth;

class InitializationBO {

    private $logger;
    private $db;
    private $ksh;
    private $mtgId;
    private $piece;
    private $mtgCode;
    private $termEnd1;
    private $termEnd2;
    private $companyId;
    private $companyIdOrig;
    private $parentMortgageId;
    private $parentMortgageCode;
    private $investorIdInv;
    private $companyIdInv;
    private $grossInv;
    private $rateInv;
    private $paymentInv;
    private $discountInv;
    private $priceInv;
    private $yieldInv;
    private $internalInv;  
    private $saleInvRow   = array();
    private $investorData;

    public function __construct(ILogger $logger, IDB $db){
        $this->logger = $logger;
        $this->db = $db;
    }

    public function checkActiveQuotes($opportunityId) {
        $this->logger->info('InitializationBO->checkActiveQuotes',[$opportunityId]);

        $applicationId = $this->getApplicationId($opportunityId);

        if($applicationId == 0) {
            $response = new Response();
            $response->status  = 'error';
            $response->message = 'Application not found'; 
            return $response;
        }

        $activeQuotes = SavedQuoteTable::query()
        ->where('application_id',$applicationId)
        ->where('disburse', '=', 'Yes')
        ->where('mortgage_id', '=', 0)
        ->get();

        if(!$activeQuotes) {
            $response = new Response();
            $response->status = 'error';
            $response->message = 'There are no active quotes';
            return $response;
        }

        if(count($activeQuotes) > 1) {
            $quotes = array();
            foreach ($activeQuotes as $value) {
                $quotes[] = [
                    'savedQuoteId'   => $value->saved_quote_id,
                    'gross'          => $value->gross,
                    'applicationId'  => $value->application_id,
                ];
            }

            $activeQuotes = [
                'type'         => 'activeQuotes',
                'activeQuotes' => $quotes
            ];

            $response = new Response();
            $response->status  = 'success';
            $response->message = '';
            $response->data    = $activeQuotes;

            return $response;

        } elseif(count($activeQuotes) == 1) {

            foreach ($activeQuotes as $key => $value) {
                $savedQuoteId = $value->saved_quote_id;
            }

            $pendingIssues = $this->checkPendingIssues($applicationId,$savedQuoteId);

            if ($pendingIssues == false) {
                $quoteData = $this->getQuoteData($applicationId, $savedQuoteId);

                $response = new Response();
                $response->status  = 'success';
                $response->message = '';
                $response->data    = $quoteData;

                return $response;
            } else {

                $response = new Response();
                $response->status  = 'success';
                $response->message = '';
                $response->data    = $pendingIssues;

                return $response;
            }
        }

        return false;
    }

    public function quoteSelected($applicationId, $savedQuoteId) {

        $this->logger->info('InitializationBO->quoteSelected',[$applicationId]);

        $pendingIssues = $this->checkPendingIssues($applicationId,$savedQuoteId);

        if ($pendingIssues == false) {
            
            $quoteData = $this->getQuoteData($applicationId, $savedQuoteId);

            $response = new Response();
            $response->status  = 'success';
            $response->message = '';
            $response->data    = $quoteData;

            return $response;


        } else {
            $response = new Response();
            $response->status  = 'success';
            $response->message = '';
            $response->data    = $pendingIssues;

            return $response;
        }
    }

    public function checkPendingIssues($applicationId,$savedQuoteId) {

        $pendingIssues = array();

        $propertyRuralRrbanNA = $this->propertyRuralRrbanNA($applicationId);
        $wrongPropertyPos     = $this->wrongPropertyPos($savedQuoteId);
        $hasProperty          = $this->hasProperty($savedQuoteId);
        $sameProperty         = $this->sameProperty($savedQuoteId);
        $investorValidate     = $this->investorValidate($savedQuoteId);

        if($propertyRuralRrbanNA == true) {
           $pendingIssues[] = [
            'name' => 'You must select a value for the Rural/Urban selection on the Mailing/Properties tab before you initialize this mortgage!'
           ];
        }
   
        if($wrongPropertyPos == true) {
            $pendingIssues[] = [
                'name' => 'Go to quote page as the keep quote you are trying to initialize has one or all of the property positions selected as "N/A". A position must be select for all properties you are trying to secure in order to initialize the quote'
            ];              
        }

        if($hasProperty == false) {
            $pendingIssues[] = [
                'name' => 'Go to the quote page and either save a quote with "enable" field selected as "Yes" or on the quote page select one or multiple keep quotes with "enable" field to "Yes"'
            ];
        }

        if($sameProperty == false) {
            $pendingIssues[] = [
                'name' => 'The quote you are trying to initialize does not match with the properties you have selected on the "Mailing / Properties" page under the field "Part of Security" as "Yes" or "No". You may have to either delete or select "Enable" to "no" on the existing quote and then re-Keep your quote. Selecting the enable field to "Yes" and properly select the position you are expecting on the respective properties showing. If you are unsure then talk to a system administrator.'
            ];
        }

        if($investorValidate == false) {            
            $pendingIssues[] = [
                'name' => 'The quote you are trying to initialize does not have any potential funder selected. Go to the quote page and click in the Sale button to choose an investor to this quote. Each quote should have at least one investor with committed equals Yes.'
            ];
        }

        if (count($pendingIssues) > 0) {

            $issues = [
                'type'          => 'pendingIssues',
                'pendingIssues' => $pendingIssues
            ];

            return $issues;
        } else {
            return false;
        }
    }

    public function getQuoteData($applicationId, $savedQuoteId) {

        $quoteData = array();

        $savedQuoteTable = SavedQuoteTable::query()
        ->where('saved_quote_id',$savedQuoteId)
        ->first();

        if ($savedQuoteTable) {
            
            $gross = $savedQuoteTable->gross;

            if (isset($savedQuoteTable->second_prime)) {

                $numericValue = preg_replace("/[^0-9.]/", "", $savedQuoteTable->second_prime) * 1;

                if ($numericValue > 0) {
                    $variableMtg = 'yes';
                } else {
                    $variableMtg = 'no';
                }
            } else {
                $variableMtg = 'no'; 
            }


            $variableDate = new DateTime($savedQuoteTable->first_pmt_date);
            $variableDate->modify('+1 day');            
            $variableDate->modify('+11 months');
 
            $termDueDate = new DateTime($savedQuoteTable->first_pmt_date);
            $termDueDate->modify('+'.($savedQuoteTable->loan - 1). 'months');

            $isMR = $this->checkisMR($savedQuoteTable->saved_quote_id);

            $quoteData = [
                'savedQuoteId'   => $savedQuoteTable->saved_quote_id,
                'applicationId'  => $savedQuoteTable->application_id,
                'net'            => $savedQuoteTable->net,
                'loan'           => $savedQuoteTable->loan,
                'clientAuthDate' => new DateTime($savedQuoteTable->client_auth_date),
                'legal'          => $savedQuoteTable->legal,
                'amort'          => $savedQuoteTable->amort,
                'intCommDate'    => new DateTime($savedQuoteTable->int_comm_date),
                'appr'           => $savedQuoteTable->appr,
                'interestRate'   => $savedQuoteTable->int,
                'noInterest'     => $savedQuoteTable->no_interest,
                'firstPmtDate'   => new DateTime($savedQuoteTable->first_pmt_date),
                'variableDate'   => $variableDate,
                'termDueDate'    => $termDueDate,
                'secondPrime'    => $savedQuoteTable->second_prime,
                'secondYear'     => $savedQuoteTable->second_year, 
                'primePlus'      => $savedQuoteTable->prime_plus, 
                'mip'            => $savedQuoteTable->mip,
                'variableMtg'    => $variableMtg,
                'discount'       => $savedQuoteTable->discount,
                'broker'         => $savedQuoteTable->broker,
                'comp'           => $savedQuoteTable->comp,
                'documentation'  => $savedQuoteTable->documentation,
                'misc'           => $savedQuoteTable->misc,
                'month'          => $savedQuoteTable->month,
                'assnRent'       => $savedQuoteTable->assn_rent,
                'gross'          => $savedQuoteTable->gross,
                'loanCategory'   => $savedQuoteTable->loan_category,
                'holdBack'       => $savedQuoteTable->hold_back,
                'specialPricing' => $savedQuoteTable->special_pricing,
                'firstChequeDate'  => $savedQuoteTable->first_cheque_date,
                'firstChequeAmt'   => $savedQuoteTable->first_cheque_amt,
                'lastChequeDate'   => $savedQuoteTable->last_cheque_date,
                'regularChequeAmt' => $savedQuoteTable->regular_cheque_amt,
                'chequeLocation'   => $savedQuoteTable->cheque_location,
                'ey'               => $savedQuoteTable->ey,
                'isMR'             => $isMR
            ];
        }

        $query = "select b.position, c.* 
                    from saved_quote_table a
                    join saved_quote_positions_table b on b.saved_quote_id = a.saved_quote_id 
                    join property_table c on c.application_id = a.application_id and c.idx = b.idx 
                   where a.saved_quote_id = ?
                     and part_of_security = 'Yes'";
        $res = $this->db->select($query, [$savedQuoteId]);

        $propertyData = array();
        foreach ($res as $key => $value) {
            $address = Utils::oneLineAddress($value->unit_number, $value->street_number, $value->street_name, $value->street_type, $value->street_direction, $value->city, $value->province, $value->postal_code);

            $propertyData[] = [
                'titleHolders'   => $value->title_holders,
                'address'        => $address,
                'link'           => '',
                'position'       => $value->position,
                'alpineInterest' => $value->alpine_interest,
                'propertyPid'    => $value->pid,
                'legal'          => $value->legal
            ];
        }
        
        $grossAmt2 = preg_replace("[^0-9.]","",$gross);
        
        $nsfFee = $this->getNsfFeeByDate($grossAmt2,date('Y-m-d'));

        $applicationTable = ApplicationTable::query()
        ->where('application_id',$applicationId)
        ->first();

        $applicationData = array();
        if ($applicationTable) {

            $applicationData = [
                'agent'        => $applicationTable->agent,
                'signingAgent' => $applicationTable->signing_agent,
                'company'      => $applicationTable->company,
                'fundingDate' => new DateTime($applicationTable->funding_date),
                'nsfFee'      => $nsfFee,
                'solicit'     => $applicationTable->solicit,
                'er'          => $applicationTable->er
            ];
        }

        $userId    = 99;
        $userData  = $this->getUserData($userId);
        $agents    = $this->getAgents($userData->default_company_id, $userData->admin);
        $companies = $this->companies($userData->default_company_id);
        $locations = $this->getLocations();
        $positions = $this->getPositions();       

        $data = array();

        $data = [
            'type'            => 'quoteData',
            'quoteData'       => $quoteData,
            'propertyData'    => $propertyData,
            'applicationData' => $applicationData,
            'agents'          => $agents,
            'companies'       => $companies,
            'locations'       => $locations,
            'positions'       => $positions,
        ]; 

        return $data;
    }

    public function getPositions() {

        $positionsTable = PositionsTable::query()
        ->orderBy('name')
        ->get();
        
        $positions = array();
        if ($positionsTable) {
            foreach ($positionsTable as $key => $value) {
                $positions[] = [
                    'id'   => $value->id,
                    'name' => $value->name
                ];
            }
        }
        
        return $positions;
    }

    public function getLocations() {

        $branchesTable = BranchesTable::query()
        ->orderBy('name')
        ->get();

        $locations = array();
        if ($branchesTable) {
            foreach ($branchesTable as $key => $value) {
                $locations[] = [
                    'id'   => $value->id,
                    'name' => $value->name
                ];
            }
        }

        return $locations;
    }

    public function getApplicationId($opportunityId) {

        $applicationId = 0;

        if(isset($opportunityId) && !is_null($opportunityId)) {

            $sfi = new SalesforceIntegration($this->db, $this->logger);
            if($sfi->getBySalesforceId('Opportunity', $opportunityId)) {

                $applicationId = $sfi->getObjectId();
            }
        }

        return $applicationId;
    }

    public function checkisMR($savedQuoteId) {

        $isMR = 'No';

        $query = "select fm_committed, gm_committed, investor_id 
                    from sale_investor_table 
                   where saved_quote_id = ?";
        $res = $this->db->select($query, [$savedQuoteId]);

        $micInvestors = array(31, 100, 248, 1919, 1971, 2042);

        foreach($res as $value) {
            if($value->fm_committed == 'Yes') {
                if(in_array($value->investor_id, $micInvestors)) {
                    $isMR = 'Yes';
                }
            }
        }

        return $isMR;
    }

    public function wrongPropertyPos($savedQuoteId) {

        $wrongPropertyPos = false;

        $query = 'select a.evaluate_by, b.position, c.part_of_security 
                  from saved_quote_table a
                  join saved_quote_positions_table b on b.saved_quote_id = a.saved_quote_id 
                  join property_table c on c.application_id = a.application_id and c.idx = b.idx 
                  where a.saved_quote_id = ?';
        $res = $this->db->select($query, [$savedQuoteId]);
        
        if ($res) {
            foreach ($res as $key => $value) {
                if ($value->evaluate_by != 'cl' && $value->position == 'N/A' && $value->part_of_security == 'Yes') {
                    $wrongPropertyPos = true;
                }
            }
        }

        return $wrongPropertyPos;

    }

    public function propertyRuralRrbanNA($applicationId) {

        $propertyRuralRrbanNA = false;

        $propertyTable = PropertyTable::query()
        ->where('application_id',$applicationId)
        ->orderBy('idx')
        ->orderBy('property_id')
        ->get();

        if ($propertyTable) {
            foreach ($propertyTable as $key => $value) {
                if ($value->rural_urban == 'N/A') {
                    $propertyRuralRrbanNA = true;
                }
                
            }
        }

        return $propertyRuralRrbanNA;
    }

    public function hasProperty($savedQuoteId) {

        $hasProperty = false;

        $query = 'select a.evaluate_by, b.position
                  from saved_quote_table a
                  join saved_quote_positions_table b on b.saved_quote_id = a.saved_quote_id 
                  where a.saved_quote_id = ?';
        $res = $this->db->select($query, [$savedQuoteId]);

        if ($res) {
            foreach ($res as $key => $value) {
                if ($value->evaluate_by == 'cl' || $value->position != 'N/A') {
                    $hasProperty = true;
                }
            }
        }

        return $hasProperty;
    }

    public function sameProperty($savedQuoteId) {

        $sameProperty = true;

        $query = 'select b.position, c.part_of_security 
                  from saved_quote_table a
                  join saved_quote_positions_table b on b.saved_quote_id = a.saved_quote_id 
                  join property_table c on c.application_id = a.application_id and c.idx = b.idx 
                  where a.saved_quote_id = ?';
        $res = $this->db->select($query, [$savedQuoteId]);

        if ($res) {
            foreach ($res as $key => $value) {
                if ($value->position != 'N/A' && $value->part_of_security != 'Yes') {
                    $sameProperty = false;
                }
            }
        }

        return $sameProperty;
    }

    public function investorValidate($savedQuoteId) {

        $investorValidate = false;

        $saleInvestorTable = SaleInvestorTable::query()
        ->where('saved_quote_id',$savedQuoteId)
        ->get();

        if ($saleInvestorTable) {
            foreach ($saleInvestorTable as $key => $value) {
                if ($value->fm_committed == 'Yes') {
                    $investorValidate = true;
                }
            }
        }

        return $investorValidate;
    }

    /*public function oneLineAddress($unitNumber, $streetNumber, $streetName, $streetType, $streetDirection, $city, $province, $postalCode) {

        $addr = "";
        $flag = true;

        if(strcmp($unitNumber, "") != 0)
            $addr .= $unitNumber."-";

        $addr .= $streetNumber." ".$streetName." ".$streetType;

        if(strcmp($streetDirection, "N/A") != 0) {
            $addr .= " ".$streetDirection;
        }
    
        $addr .= ", ".$city." ".$province." ".$postalCode;
        $url   = "https://www.google.ca/maps/place/".$streetNumber."+".$streetName."+".$streetType."+".$city."+".$province."+".$postalCode."+Canada";
    
        $data = [
            'addr' => $addr,
            'url'  => $url
        ];

        return $data;
    }*/

    public function getNsfFeeByDate($amount, $date) {

        $nsfFee = NsfFee::where('loan_amount_min', '<=', $amount)
                        ->where('loan_amount_max', '>=', $amount)
                        ->where('effective_at', '<=', $date)
                        ->orderBy('effective_at', 'desc')
                        ->first();

        if ($nsfFee) {
            return $nsfFee->nsf_fee_amount;
        } else {
            return 0;
        }
    }

    public function getUserData($userId) {

        $userData = UsersTable::query()
        ->where('user_id',$userId)
        ->first();

        return $userData;
    }
    
    public function getAgents($defaultCompanyId, $admin) {

        if (($defaultCompanyId == "301") && ($admin == "no"))  {
            $query = 'select * from users_table where default_company_id = 301 and inuse="yes"  order by user_lname desc';
        } elseif (($defaultCompanyId == "601") && ($admin == "no"))  {
            $query = 'select * from users_table where default_company_id = 601 and inuse="yes"  order by user_lname desc';
        } elseif (($defaultCompanyId == "701") && ($admin == "no"))  {
            $query = 'select * from users_table where default_company_id = 701 and inuse="yes"  order by user_lname desc';
        } elseif (($defaultCompanyId == "401") && ($admin == "no"))  {
            $query = 'select * from users_table where default_company_id = 401 and inuse="yes"  order by user_lname desc';
        } elseif ($admin == "no")  {
            $query = 'select * from users_table where default_company_id not in (301,601,701,401) and inuse = "yes" order by user_lname desc';
        } else {
            $query = 'select * from users_table where inuse="yes" order by user_lname desc';
        }
        $res = $this->db->select($query);

        $agents = array();
        foreach($res as $key => $value) {
            $agents[] = [
                'userId' => $value->user_id,
                'name'   => $value->user_fname.' '.$value->user_lname
            ];
        }

        return $agents;
    }

    public function companies($defaultCompanyId) {

        $companies = array();

        $alpineCompaniesTable = AlpineCompaniesTable::query()
        ->where('id','<','5000')
        ->orderBy('abbreviation')
        ->get();

        $companies[] = [
            'id'           => 0,
            'abbreviation' => 'Create Dummy Card'
        ];
        
        if($defaultCompanyId == "701" || $defaultCompanyId == "601" || $defaultCompanyId == "301" || $defaultCompanyId == "401") {

        } else {
            $companies[] = [
                'id'           => '0:1',
                'abbreviation' => 'Presell Card - ACL'
            ];

            $companies[] = [
                'id'           => '0:3',
                'abbreviation' => 'Presell Card - AMC'
            ];
        }

        if ($alpineCompaniesTable) {
            foreach ($alpineCompaniesTable as $key => $value) {
                $companies[] = [
                    'id'           => $value->id,
                    'abbreviation' => $value->abbreviation
                ];
            }
        }

        return $companies;
    }

    public function initialize($fields) {

        $savedQuoteTable = SavedQuoteTable::query()
        ->where('saved_quote_id',$fields['savedQuoteId'])
        ->first();

        if ($savedQuoteTable && $savedQuoteTable->mortgage_id > 0) {
            $this->logger->info('InitializationBO->initialize --- Quote already initialized',['savedQuoteId' => $fields['savedQuoteId'], 'ApplicationId' => $fields['applicationId']]);
            return [
                'status'  => 'error',
                'message' => 'Quote already initialized'
            ];
        }

        $this->logger->info('InitializationBO->initialize',[$fields]);

        $this->db->beginTransaction();
        try {
            $userIdSf         = $fields['userId'];;
            $applicationId    = $fields['applicationId'];
            $savedQuoteId     = $fields['savedQuoteId'];
            $agent            = $fields['agent'];
            $signingAgent     = $fields['signingAgent'];
            $brokerGroup      = $fields['brokerGroup'];
            $institution      = $fields['institution'];
            $transit          = $fields['transit'];
            $account          = $fields['account'];
            $payeeName        = $fields['payeeName'];
            $firstChequeDate  = $fields['firstChequeDate'];
            $firstChequeAmt   = $fields['firstChequeAmt'];
            $lastChequeDate   = $fields['lastChequeDate'];
            $regularChequeAmt = $fields['regularChequeAmt'];
            $chequeLocation   = $fields['chequeLocation'];
            $company          = $fields['company'];
            $nsfFee           = $fields['nsfFee'];
            //$solicitTerm      = $fields['solicitTerm'];
            $cpsInstruct      = $fields['cpsInstruct'];
            
            $userId           = $this->getUserId($userIdSf);

            if ($company == '301') {
                $paidoutBy = 'By GET';
            } elseif ($company == '601') {
                $paidoutBy = 'By AAR';
            } elseif ($company == '401') {
                $paidoutBy = 'By AOL';
            } else {
                $paidoutBy = 'By Alpine';
            }

            $savedQuoteTable = $this->getSavedQuoteTable($savedQuoteId);
    
            if($savedQuoteTable) {
                if($savedQuoteTable->disburse == 'No') {
                    return [
                        'status'  => 'error',
                        'message' => 'Quote must be active'
                    ];
                }
            }

            $application = ApplicationTable::find($applicationId);
            if(!$application) {
                return [
                    'status'  => 'error',
                    'message' => 'Application not found'
                ];
            }

            $this->companyIdOrig = $application->company;

            $institutionId = 0;

            $isMR = $this->checkisMR($savedQuoteId);

            if ($isMR == 'Yes') {
                $institutionId = $this->getInstitutionId($institution);

                if ($institutionId == 0) {
                    return [
                        'status'  => 'error',
                        'message' => 'PAP Bank Institution code is incorrect'
                    ];
                }
            }

            $this->setStatus($applicationId, $company, $userId, $savedQuoteId);

            $this->saleInvRow = $this->getSaleInvestorTable($savedQuoteId);

            $loop = 1;
            if (isset($this->saleInvRow)) {
                $this->getInvestorData($savedQuoteId);
                $loop = 3;
                if (!empty($this->saleInvRow->cp_amount)){
                    $loop = 4;
                }
            }

            for($i = 0; $i < $loop; $i++) {

                if ($i == 0) {
                    $this->piece = '';
                } elseif ($i == 1) {
                    $this->piece = 'ap';
                    $this->createPiece();
                } elseif ($i == 2) {
                    $this->piece = 'bp';
                    $this->createPiece();
                } elseif ($i == 3) {
                    $this->piece = 'cp';
                    $this->createPiece();
                }

                $mortgageId = $this->createMortgage(
                    $applicationId,
                    $savedQuoteId,
                    $company,
                    $userId,
                    $nsfFee,
                    $agent,
                    $signingAgent,
                    $brokerGroup,
                    $firstChequeDate,
                    $lastChequeDate,
                    $chequeLocation,
                    $cpsInstruct,
                    $firstChequeAmt,
                    $regularChequeAmt
                );

                $this->checkPropertyMortgagesTable($applicationId, $paidoutBy); 
                
                if ( empty($signingAgent) ) {
                    $authorId = $agent;
                } else {
                    $authorId = $signingAgent;
                }
        
                $applicationTable = $this->getApplicationTable($applicationId);
                $source  = $applicationTable->source;
                $source2 = $applicationTable->source2;
    
                $sourcesTable = $this->getSourcesTable();        
    
                $b2bName = '';
                if ($sourcesTable) {
                    foreach ($sourcesTable as $key => $value) {
                        if($value->id == $source || $value->id == $source2) {
                            $b2bName = str_replace('Referral - ','',$value->source);
                            $b2bName = str_replace(' Corp','',$b2bName);
                        }            
                    }
                }
    
                $this->insertNotesTable($applicationId,$b2bName,$authorId,$savedQuoteTable->special_pricing);
    
                if ($this->termEnd2 != null) {
                    MortgageTable::where('mortgage_id', $this->mtgId)
                    ->update(['due_date' => $this->termEnd2]);
                } else {
                    MortgageTable::where('mortgage_id', $this->mtgId)
                    ->update(['due_date' => $this->termEnd1]);  
                }
    
                $this->papBank($userId,$institution,$transit,$account,$payeeName);
            }

            $this->db->commit();

        } catch(\Throwable $e) {
            $this->logger->error('InitializationBO->initialize', [$e->getMessage(),json_encode($e->getTraceAsString())]);
            $this->db->rollback();
            return false;
        }

        $this->updateSalesforce($mortgageId, $applicationId, $userId);

        if ($savedQuoteId > 0) {

            $this->logger->info('InitializationBO->initialize - Sync quote to salesforce', [$savedQuoteId]);

            $salesforceBO = new SalesforceBO($this->logger, $this->db);
            $salesforceBO->syncQuote($savedQuoteId);
        }

        return [
            'mortgageCode' => $this->parentMortgageCode,
        ];
    }

    public function papBank($userId,$institution,$transit,$account,$payeeName) {

        $this->logger->info('InitializationBO->papBank ',[$userId,$institution,$payeeName]);

        if(!empty($this->mtgId) && !empty($institution)) {

            $institutionId = $this->getInstitutionId($institution);

            $payeeNameAux = trim($payeeName);

            $papBank = new PapBank();
            $papBank->mortgage_id    = $this->mtgId;
            $papBank->category_id    = 536;
            $papBank->institution_id = $institutionId;
            $papBank->transit        = $transit;
            $papBank->account        = $account;
            $papBank->payee_name     = $payeeNameAux;
            $papBank->created_by     = $userId;
            $papBank->updated_by     = $userId;
            $papBank->save();
        }        
    }

    public function insertNotesTable($applicationId, $b2bName, $authorId, $specialPricing) { 

        $this->logger->info('InitializationBO->insertNotesTable ',[$applicationId, $b2bName, $authorId, $specialPricing]);

        if($this->companyId == 701) {
            $noteText = " Solicit: TACL Flagged for Solicitation - The previous file was initialized with the special pricing selection: (" . $specialPricing . ") " . $this->mtgCode;

            if($b2bName != '') {
                $noteText .= '<br>DNS as this is a ' . $b2bName . ' Administrative file. Please contact the fund managers if you have questions';
            }
            $note = new NotesTable;
            $note->application_id  = $applicationId;
            $note->author_id       = $authorId;
            $note->last_updated_by = $authorId;
            $note->category_id     = 2;
            $note->note_date_time  = now();
            $note->last_updated    = now();
            $note->followup_date   = now()->addDays(31);
            $note->follower_up     = $authorId;
            $note->followed_up     = 'no';
            $note->note_text       = $noteText;
            $note->save();
        }
    }

    public function checkPropertyMortgagesTable($applicationId, $paidoutBy) {
        $this->logger->info('InitializationBO->checkPropertyMortgagesTable ',[$applicationId, $paidoutBy]);

        $propertyTable = PropertyTable::query()
        ->where('application_id', $applicationId)
        ->get();

        foreach($propertyTable as $key => $value) {
            PropertyMortgagesTable::where('property_id', $value->property_id)
            ->where('payout', $paidoutBy)
            ->delete();
        }
    }

    public function setStatus($applicationId, $company, $userId, $savedQuoteId) {
        if($company == '0:1' || $company == '0:3') {
            $status = '14';
            $this->ksh = 5;
        } else {
            $status = '13';
            $this->ksh = 4;
        }

        $applicantTable = ApplicationTable::find($applicationId);
        if($applicantTable) {
            $applicantTable->status = $status;
            $applicantTable->save();

            $statusInfo = new StatusInfo();
            $statusInfo->application_id = $applicationId;
            $statusInfo->quote_id = $savedQuoteId;
            $statusInfo->status_id = $status;
            $statusInfo->user_id = $userId;
            $statusInfo->status_date = new DateTime();
            $statusInfo->save();
        }
    }

    public function createMortgage(
        $applicationId,
        $savedQuoteId,
        $company,
        $userId,
        $nsfFee,
        $agent,
        $signingAgent,
        $brokerGroup,
        $firstChequeDate,
        $lastChequeDate,
        $chequeLocation,
        $cpsInstruct,
        $firstChequeAmt,
        $regularChequeAmt) {

        $saleInvestorTable = $this->getSaleInvestorTable($savedQuoteId);
        $this->saleInvRow  = $saleInvestorTable;

        if ($company == '0') {
            $this->mtgCode = 'DUMMY CARD';
            $this->companyId = 0;
            $presellCompanyId = 0;
        } else {
            if($company == '0:1') {
                $this->companyId = 0;
                $presellCompanyId = 1;

            } elseif($company == '0:3') {
                $this->companyId = 0;
                $presellCompanyId = 3;

            } else {
                $this->companyId = $company;
                $presellCompanyId = 0;
            }
            $this->mtgCode = $this->getMortgageCode($presellCompanyId);
        }

        $micArray = array(5,182,16);
        $amurAmount = 0;

        if($saleInvestorTable) {
            $abLoan = 'm_mtg';
            if (!empty($saleInvestorTable->ap_amount) && in_array($saleInvestorTable->ap_inv_co,$micArray)) {
                $amurAmount += $saleInvestorTable->ap_amount;
            }
            if (!empty($saleInvestorTable->bp_amount) && in_array($saleInvestorTable->bp_inv_co,$micArray)) {
                $amurAmount += $saleInvestorTable->bp_amount;
            }
            if (!empty($saleInvestorTable->cp_amount) && in_array($saleInvestorTable->cp_inv_co,$micArray)) {
                $amurAmount += $saleInvestorTable->cp_amount;
            }

            if ($saleInvestorTable->bp_inv_co == 200){
                $amurManagement = 'No';
            } else{
                $amurManagement = 'Yes';
            }

        } else {
            $abLoan         = 'No';
            $amurManagement = 'Yes';
        }

        return $this->createMortgageEntry(
            $applicationId,
            $savedQuoteId,
            $presellCompanyId,
            $userId,
            $abLoan,
            $amurManagement,
            $amurAmount,
            $nsfFee,
            $agent,
            $signingAgent,
            $brokerGroup,
            $firstChequeDate,
            $lastChequeDate,
            $chequeLocation,
            $cpsInstruct,
            $firstChequeAmt,
            $regularChequeAmt
        );
    }

    public function getMortgageCode($presellCompanyId) {

        $this->mtgCode = null;

        if($this->piece == '') {
            $id = $presellCompanyId;
            if($this->companyId != '') {
                $id = $this->companyId;
            }
            
            $alpineCompaniesTable = AlpineCompaniesTable::query()
            ->where('id', $id)
            ->first();
    
            if($alpineCompaniesTable) {
                $this->mtgCode = $alpineCompaniesTable->abbr . '' . $alpineCompaniesTable->current_number;
    
                AlpineCompaniesTable::where('id', $id)
                ->update(['current_number' => $alpineCompaniesTable->current_number + 1]);
            }
        } else {
            $id = (!empty($this->companyIdInv)) ? $this->companyIdInv : 1;

            $alpineCompaniesTable = AlpineCompaniesTable::query()
            ->where('id', $this->companyIdOrig)
            ->first();
    
            if($alpineCompaniesTable) {
                $lender = AlpineCompaniesTable::query()
                ->where('id', $id)
                ->first();

                $this->mtgCode = $alpineCompaniesTable->abbr . $lender->abbr . $alpineCompaniesTable->current_number;
    
                AlpineCompaniesTable::where('id', $this->companyIdOrig)
                ->update(['current_number' => $alpineCompaniesTable->current_number + 1]);
            }
        }

        return $this->mtgCode;
    }

    public function createMortgageEntry(
        $applicationId,
        $savedQuoteId,
        $presellCompanyId,
        $userId,
        $abLoan,
        $amurManagement,
        $amurAmount,
        $nsfFee,
        $agent,
        $signingAgent,
        $brokerGroup,
        $firstChequeDate,
        $lastChequeDate,
        $chequeLocation,
        $cpsInstruct,
        $firstChequeAmt,
        $regularChequeAmt) {

        $referringAgent = $this->getReferringAgent($applicationId);

        $mortgageBO = new MortgageBO($this->logger, $this->db);
        $ltv = $mortgageBO->getLtvByQuote($savedQuoteId);

        $savedQuoteTable = SavedQuoteTable::query()
        ->where('saved_quote_id', $savedQuoteId)
        ->first();

        $applicationTable = ApplicationTable::query()
        ->where('application_id', $applicationId)
        ->first();

        $termDueDate = new DateTime($savedQuoteTable->first_pmt_date);
        $termDueDate->modify('+'.($savedQuoteTable->loan - 1). 'months');

        $tmpVar = preg_replace("[^0-9.]", "", $savedQuoteTable->second_year);
        if ( (!empty($savedQuoteTable->second_year)) && ($savedQuoteTable->loan > 12) && ($tmpVar <> 0) ){
            $comments = " Year 2 = Prime + ".preg_replace("[^0-9.]", "", $savedQuoteTable->second_year)."% ";
        }
        else {
            $comments = " ";
        }

        $directFunded = $this->directFunded($savedQuoteId);

        if (isset($savedQuoteTable->second_prime)) {

            $numericValue = preg_replace("/[^0-9.]/", "", $savedQuoteTable->second_prime) * 1;
        
            if ($numericValue > 0) {
                $variableMtg = 'yes';
            } else {
                $variableMtg = 'no';
            }
        } else {
            $variableMtg = 'no'; 
        }
 
        $variableDate = new DateTime($savedQuoteTable->first_pmt_date);
        $variableDate->modify('+1 day');            
        $variableDate->modify('+11 months');
        $intCommDate = date('Y-m-d', strtotime($savedQuoteTable->int_comm_date));

        if ($this->parentMortgageCode == null) {
            $this->parentMortgageCode = $this->mtgCode;
        }

        $mortgageTable = new MortgageTable;
        $mortgageTable->dealer_id              = 0;
        $mortgageTable->recourse_type          = '';
        $mortgageTable->dealer_discount        = 0;
        $mortgageTable->dealer_reserve         = 0;
        $mortgageTable->dealer_recourse        = 0;
        $mortgageTable->net_to_dealer          = 0;
        $mortgageTable->mortgage_id            = null; 
        $mortgageTable->application_id         = $applicationId;
        $mortgageTable->mortgage_code          = $this->mtgCode;
        $mortgageTable->company_id             = $this->companyId;

        if ($this->piece == '') {
            $mortgageTable->insurance_fee          = $savedQuoteTable->insurance_fee;
            $mortgageTable->amur_management        = $amurManagement;
            $mortgageTable->status_id              = 22;
            $mortgageTable->status_date            = now();
            $mortgageTable->gdsr                   = $savedQuoteTable->gdsr;
            $mortgageTable->tdsr                   = $savedQuoteTable->tdsr;
            $mortgageTable->dscr                   = $savedQuoteTable->dscr;
        } else {
            $mortgageTable->ab_internal            = $this->internalInv;
            $mortgageTable->parent                 = $this->parentMortgageId;
        }

        $mortgageTable->presell_company_id     = $presellCompanyId;
        $mortgageTable->nsf_fee                = $nsfFee;
        $mortgageTable->int_comm_date          = $intCommDate;
        $mortgageTable->mortgage_int_comm_date = $intCommDate;
        $mortgageTable->client_auth_date       = $savedQuoteTable->client_auth_date;
        $mortgageTable->first_pmt_due_date     = $savedQuoteTable->first_pmt_date;
        $mortgageTable->net_amt                = $savedQuoteTable->net;
        $mortgageTable->legal_fee              = $savedQuoteTable->legal;
        $mortgageTable->application_fee        = $savedQuoteTable->appr;
        $mortgageTable->brokerage_fee          = $savedQuoteTable->broker;

        if($this->piece == '') {
            $mortgageTable->discount_fee           = $savedQuoteTable->discount;
            $mortgageTable->gross_amt              = $savedQuoteTable->gross;
            $mortgageTable->interest_rate          = $savedQuoteTable->int;
            $mortgageTable->current_balance        = $savedQuoteTable->gross;
            $mortgageTable->ab_loan                = $abLoan;
        } else {
            $mortgageTable->discount_fee           = $this->discountInv;
            $mortgageTable->gross_amt              = $this->grossInv;
            $mortgageTable->interest_rate          = $this->rateInv;
            $mortgageTable->current_balance        = $this->grossInv;
            $mortgageTable->ab_loan                = 'c_mtg';
        }

        $mortgageTable->misc_fee               = $savedQuoteTable->misc;
        $mortgageTable->term_length            = $savedQuoteTable->loan;
        $mortgageTable->compounding            = $savedQuoteTable->comp;
        $mortgageTable->second_year            = $savedQuoteTable->second_year;
        $mortgageTable->second_year_prime      = $savedQuoteTable->second_prime;
        $mortgageTable->second_year_prime_plus = $savedQuoteTable->prime_plus;
        $mortgageTable->monthly_pmt            = $savedQuoteTable->month;
        $mortgageTable->mip                    = $savedQuoteTable->mip;
        $mortgageTable->type_of_loan           = $savedQuoteTable->type_of_loan;
        $mortgageTable->documentation          = $savedQuoteTable->documentation;
        $mortgageTable->assignment_of_rent     = strtolower($savedQuoteTable->assn_rent);
        $mortgageTable->hold_back_required     = strtolower($savedQuoteTable->hold_back);
        $mortgageTable->ila                    = $applicationTable->ila;
        $mortgageTable->expected_funding_date  = $applicationTable->funding_date;
        $mortgageTable->is_funded              = 'no';
        $mortgageTable->funding_date           = '0000-00-00';
        $mortgageTable->ksh                    = $this->ksh;
        $mortgageTable->ksh_flag               = 1;
        $mortgageTable->is_sold                = 'no';
        $mortgageTable->investor_id            = 0;
        $mortgageTable->source                 = $applicationTable->source;
        $mortgageTable->agent                  = $agent;
        $mortgageTable->signing_agent          = $signingAgent;
        $mortgageTable->brokerGroup            = $brokerGroup;
        $mortgageTable->cpsInstruct            = $cpsInstruct;
        $mortgageTable->escalation             = $variableMtg; 
        $mortgageTable->escalation_date        = $variableDate;
        $mortgageTable->cheque_branch_location = $chequeLocation; 
        $mortgageTable->amortization           = $savedQuoteTable->amort;
        $mortgageTable->mortgage_registration_number = '';
        $mortgageTable->assignment_of_rents_registration_number = '';  
        $mortgageTable->signed_date            = $applicationTable->signed_date;
        $mortgageTable->ltv                    = $ltv['ltv'];
        $mortgageTable->initial_ltv            = $ltv['ltv'];
        $mortgageTable->initializer            = $userId;
        $mortgageTable->purpose                = $applicationTable->purpose;
        $mortgageTable->comments               = $comments;
        $mortgageTable->special_pricing        = $savedQuoteTable->special_pricing;
        $mortgageTable->no_interest            = $savedQuoteTable->no_interest;
        $mortgageTable->er                     = $savedQuoteTable->ey;
        $mortgageTable->solicit                = $applicationTable->solicit;
        $mortgageTable->direct_funded          = $directFunded;
        $mortgageTable->mortgage_type          = $savedQuoteTable->mortgage_type;
        $mortgageTable->loan_category          = $savedQuoteTable->loan_category;
        $mortgageTable->amur_amount            = $amurAmount;
        $mortgageTable->referring_agent_id     = $referringAgent;
        $mortgageTable->save();

        $mortgageId = $mortgageTable->mortgage_id;
        $this->mtgId = $mortgageId;
        if ($this->parentMortgageId == null) {
            $this->parentMortgageId = $mortgageId;
        }

        $this->createMortgageNsf($mortgageId,$nsfFee,$intCommDate);

        $row22 = $this->checkDeletedMortgage($applicationId);
        $row23 = $this->checkPbPool($agent);

        if ($brokerGroup == 'Gp1' || $brokerGroup == 'Gp3' ) {
            if ($row22 == 1 && $row23 == 0) {

                $pbId = 0;

                $pbPool = PbPool::query()
                ->where('inuse', 1)
                ->where('assigned_flag', '<>', 1)
                ->orderBy('id', 'asc')
                ->get();

                if ($pbPool->isEmpty()) {

                    PbPool::where('inuse', 1)
                    ->update(['assigned_flag' => 0]);

                    $pbPool = PbPool::query()
                    ->where('inuse', 1)
                    ->get();
                }
                
                if ($pbPool) {
                    foreach ($pbPool as $key => $value) {
                        PbPool::where('id', $value->id)
                        ->update(['assigned_flag' => 1]);

                        $pbId    = $value->user_id;
                    }
                }

                $pbNewAppLog = new PbNewAppLog();
                $pbNewAppLog->application_id   = $applicationId;
                $pbNewAppLog->mortgage_id      = $mortgageId;
                $pbNewAppLog->company_id       = $this->companyId;
                $pbNewAppLog->initialize_date  = now();
                $pbNewAppLog->pb_agent         = $pbId;
                $pbNewAppLog->pb_signing_agent = $pbId;
                $pbNewAppLog->nb_agent         = $agent;
                $pbNewAppLog->nb_signing_agent = $signingAgent;            
                $pbNewAppLog->save();
            }
        }
        
        $lawyerFileTable = new LawyerFileTable();
        $lawyerFileTable->application_id           = $applicationId;
        $lawyerFileTable->mortgage_id              = $mortgageId;
        $lawyerFileTable->mortgage_code            = $this->mtgCode;
        $lawyerFileTable->company_id               = $this->companyId;
        $lawyerFileTable->Power_of_Attorney        = $savedQuoteTable->Power_of_Attorney;
        $lawyerFileTable->Partial_interest         = $savedQuoteTable->Partial_interest;
        $lawyerFileTable->est_cost                 = preg_replace("[^0-9.]", "", $savedQuoteTable->est_cost).',';
        $lawyerFileTable->lawyer_follow_up_date    = $savedQuoteTable->lawyer_follow_up_date;
        $lawyerFileTable->lawyer_agent_id          = $savedQuoteTable->lawyer_agent_id;
        $lawyerFileTable->title_due_diligence      = $savedQuoteTable->title_due_diligence;
        $lawyerFileTable->date_title_last_transfer = $savedQuoteTable->date_title_last_transfer;
        $lawyerFileTable->is_property_FC           = $savedQuoteTable->is_property_FC;
        $lawyerFileTable->gross_amt                = $savedQuoteTable->gross;
    
        $lawyerFileTable->save();        

        if ($this->companyId == '401') {
            LawyerFileTable::where('mortgage_id', $mortgageId)
            ->update(['title_due_diligence' => 'No']);
        }

        $periodLocksTable = $this->getperiodLocksTable($savedQuoteTable->int_comm_date);

        if(count($periodLocksTable) <= 0) {
            $periodDate = $savedQuoteTable->int_comm_date;
        } else {
            $periodDate = new DateTime($savedQuoteTable->int_comm_date);     
            $periodDate->modify('+1 month');
        }

        $this->insertMortgagePaymentsTable($mortgageId, $savedQuoteTable->int_comm_date, $periodDate, $savedQuoteTable->gross);

        $this->createEntriesMortgagePropertiesTable($savedQuoteId, $mortgageId);

        $this->createPmtsFromCheques($savedQuoteId, $mortgageId, $firstChequeDate, $lastChequeDate, $firstChequeAmt, $regularChequeAmt);

        $this->createEntriesInMortgageInterestRatesTable($mortgageId, $savedQuoteTable->int_comm_date, $savedQuoteTable->first_pmt_date, $savedQuoteTable->loan, $termDueDate, $savedQuoteTable->int, $savedQuoteTable->prime_plus, $userId);

        $this->createEntriesInMortgageMortgagorsTable($applicationId, $savedQuoteId, $mortgageId);
        
        if($this->piece == '') {
            $this->savedQuoteTable($savedQuoteId, $mortgageId);
        }

        $this->createInvestorTracking($mortgageId, $savedQuoteId);

        if($this->piece == '') {
            $this->createMortgageFolder($mortgageId);
        }

        return $mortgageId;
    }

    public function createMortgageFolder($mortgageId) {
        $this->logger->info('InitializationBO->createMortgageFolder ',[$mortgageId]);

        $salesforceBO = new SalesforceBO($this->logger, $this->db);
        $salesforceBO->createMortgageFolder($mortgageId);
    }

    public function updateSalesforce($mortgageId, $applicationId, $userId) {
        $this->logger->info('InitializationBO->updateSalesforce ',[$mortgageId, $applicationId, $userId]);

        $salesforceBO = new SalesforceBO($this->logger, $this->db);
        $salesforceBO->customerCare($mortgageId, $applicationId, $userId);
    }

    public function createInvestorTracking($mortgageId, $savedQuoteId) {

        $this->logger->info('InitializationBO->createInvestorTracking ',[$mortgageId, $savedQuoteId]);

        $query = 'select max(investor_tracking_id) investor_tracking_id
                  from mortgage_investor_tracking_table
                  where mortgage_id = ?';
        $res = $this->db->select($query, [$mortgageId]);

        $investorTrackingId = 1;
        if($res) {
            $investorTrackingId = ($res[0]->investor_tracking_id * 1) + 1;
        }

        $saleInvestorTable = SaleInvestorTable::query()
        ->where('saved_quote_id', $savedQuoteId)
        ->orderBy('investor_id')
        ->get();

        // KSH status should follow this rule:
        // RMIF    n     n     y     n     y
        // MII     n     n     -     n     n
        // BSF     n     n     -     y     n
        // ACL     y     y     -     -     y
        // invest  0     <> 0  -     -     0
        // Status  Sell  Hold  Hold  Hold  Hold
        
        $kshStatus = 3;

        foreach ($saleInvestorTable as $key => $value) {

            if ($value->investor_id == 0) {
                if ($value->fm_committed == 'Yes') {
                    $kshStatus = 22;
                }
            } else {
                if ($value->fm_committed == 'Yes') {
                    $kshStatus = 3;

                    if(in_array($value->investor_id, Utils::micCompanies())) {
                        $fmCommitted = $value->fm_committed;
                    } else {
                        $fmCommitted = 'If Available';
                    }
                }else {
                    $fmCommitted = $value->fm_committed;
                }

                $accrued_int = 0;
                $price = $value->price - $value->accrued_int;

                if ($this->piece == '') {
                    $grossTmp      = $value->gross_amount;
                    $discountTmp   = $value->discount;
                    $priceTmp      = $price;
                    $yieldTmp      = $value->yield;
                    $investorIdTmp = $value->investor_id;
                }else {
                    $grossTmp    = $this->grossInv;
                    if (empty($this->discountInv)) {
                        $discountTmp = 0.00;
                    } else {
                        $discountTmp = $this->discountInv;
                    }
                    $priceTmp = $this->priceInv;
                    if (empty($this->yieldInv)) {
                        $yieldTmp = 0.00;
                    } else {
                        $yieldTmp = $this->yieldInv;
                    }
                    $investorIdTmp = $this->investorIdInv;
                }                

                $mtgInvestorTracking = new MortgageInvestorTrackingTable();
                $mtgInvestorTracking->mortgage_id          = $mortgageId;
                $mtgInvestorTracking->investor_tracking_id = $investorTrackingId;
                $mtgInvestorTracking->td_lp_date           = $value->lp_date;
                $mtgInvestorTracking->td_balance_lp        = $grossTmp;
                $mtgInvestorTracking->td_accrued_int       = $accrued_int;
                $mtgInvestorTracking->td_subtotal          = $grossTmp + $accrued_int;
                $mtgInvestorTracking->td_discount          = $discountTmp;
                $mtgInvestorTracking->td_price             = $priceTmp;
                $mtgInvestorTracking->quote_date           = now();
                $mtgInvestorTracking->investor_id          = $investorIdTmp;
                $mtgInvestorTracking->agent_id             = $value->fm_approved_id;
                $mtgInvestorTracking->expected_date        = date('Y-m-d', strtotime($value->lp_date . ' -1 day'));
                $mtgInvestorTracking->committed            = $fmCommitted;
                $mtgInvestorTracking->discount             = $discountTmp;
                $mtgInvestorTracking->sale_price           = $priceTmp;
                $mtgInvestorTracking->yield                = $yieldTmp;
                $mtgInvestorTracking->hold_date            = now();
                $mtgInvestorTracking->quote_comment        = $value->comment;
                $mtgInvestorTracking->last_agent_id        = $value->last_updated_user_id;                
                $mtgInvestorTracking->save();

                $mortgageInvestorTrackingInvestor = new MortgageInvestorTrackingInvestorsTable();
                $mortgageInvestorTrackingInvestor->mortgage_id          = $mortgageId;
                $mortgageInvestorTrackingInvestor->investor_tracking_id = $investorTrackingId;
                $mortgageInvestorTrackingInvestor->investor_id          = $investorIdTmp;
                $mortgageInvestorTrackingInvestor->save();

                $investorTrackingId++;
            }
        }

        if($kshStatus == 22) {

            $mortgageTable = MortgageTable::query()
            ->where('mortgage_id', $mortgageId)
            ->get();

            if ($mortgageTable) {
                foreach ($mortgageTable as $key => $value) {
                    ApplicationTable::where('application_id', $value->application_id)
                    ->update(['presell' => 'Yes']);
                }
            }
        }

        MortgageTable::where('mortgage_id', $mortgageId)
        ->update(['ksh' => $kshStatus]);
    }

    public function savedQuoteTable($savedQuoteId, $mortgageId) {
        SavedQuoteTable::where('saved_quote_id', $savedQuoteId)
        ->update(['mortgage_id' => $mortgageId]);
    }

    public function createEntriesInMortgageMortgagorsTable($applicationId, $savedQuoteId, $mortgageId) {

        $this->logger->info('InitializationBO->createEntriesInMortgageMortgagorsTable',[$applicationId, $savedQuoteId, $mortgageId]);

        if ($this->piece === '') {

            $applicantsName = $this->getApplicantsName($applicationId);

            $query = "select b.position, c.* 
                        from saved_quote_table a
                        join saved_quote_positions_table b on b.saved_quote_id = a.saved_quote_id 
                        join property_table c on c.application_id = a.application_id and c.idx = b.idx 
                       where a.saved_quote_id = ?
                         and c.part_of_security = 'Yes'";
            $res = $this->db->select($query, [$savedQuoteId]);

            $mortgagors = array();
            foreach($res as $value) {
                if($value->position != 'N/A' && $applicantsName && count($applicantsName) > 0) {
                    $titleHolders = explode(',',$value->title_holders); 

                    if($titleHolders && count($titleHolders) > 0) {
                        foreach($titleHolders as $titleHolder) {
                            foreach($applicantsName as $applicant) {
                                if(trim($applicant['name']) == trim($titleHolder)) {                                               

                                    if($applicant['corporationId'] > 0) {
                                        $applicantId = 0;
                                        $spouseId    = 0;                                                    
                                        $type        = 'Title Holder';
                                        $corpId      = $applicant['corporationId'];
                                    } else {
                                        $applicantId = $applicant['applicantId'];
                                        $spouseId    = $applicant['spouseId'];
                                        $type        = 'Title Holder';
                                        $corpId      = 0;
                                    }

                                    if(!isset($mortgagors[$mortgageId][$spouseId][$applicantId][$corpId][$type])) {
                                        $mortgagor = new MortgageMortgagorsTable;
                                        $mortgagor->mortgage_id  = $mortgageId;
                                        $mortgagor->spouse_id    = $spouseId;
                                        $mortgagor->applicant_id = $applicantId;
                                        $mortgagor->type         = $type;
                                        $mortgagor->corp_id      = $corpId;
                                        $mortgagor->save();

                                        $mortgagors[$mortgageId][$spouseId][$applicantId][$corpId][$type] = 'x';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {

            $mortgageMortgagorsTable = MortgageMortgagorsTable::query()
            ->where('mortgage_id', $this->parentMortgageId)
            ->get();
            
            if ($mortgageMortgagorsTable && count($mortgageMortgagorsTable) > 0) {
                foreach ($mortgageMortgagorsTable as $key => $value) {
                    $mortgagor = new MortgageMortgagorsTable;
                    $mortgagor->mortgage_id  = $mortgageId;
                    $mortgagor->spouse_id    = $value->spouse_id;
                    $mortgagor->applicant_id = $value->applicant_id;
                    $mortgagor->type         = $value->type;
                    $mortgagor->corp_id      = $value->corp_id;
                    $mortgagor->save();
                }
            }
        }
    }

    public function createEntriesInMortgageInterestRatesTable($mortgageId, $intCommDate, $firstPmtDate, $loan, $termDueDate, $int, $primePlus, $userId) {

        $termStartA = $intCommDate;
        $termStartB = $firstPmtDate;

        if($loan <= 13) {
            $this->termEnd1 = $termDueDate;
        } else {
            $dateAux = new DateTime($termStartB);
            $dateAux->modify('+11 months');
            $this->termEnd1 = $dateAux;

            $termStart2 = new DateTime($termStartB);
            $termStart2->modify('+11 months');
            $termStart2->modify('+1 day');       

            $this->termEnd2 = $termDueDate;
        }

        if ($this->piece == '') {
            $intTmp = $int;
        } else {
            $intTmp = $this->rateInv;
        }

        $mortgageInterestRate = new MortgageInterestRatesTable();
        $mortgageInterestRate->mortgage_id   = $mortgageId;
        $mortgageInterestRate->term_start    = $termStartA;
        $mortgageInterestRate->term_end      = $this->termEnd1;
        $mortgageInterestRate->interest_rate = $intTmp;
        $mortgageInterestRate->new_term      = 'yes';
        $mortgageInterestRate->created_by    = $userId;
        $mortgageInterestRate->updated_by    = $userId;
        $mortgageInterestRate->save();

        if(isset($termStart2)) {
            $mortgageInterestRate = new MortgageInterestRatesTable();
            $mortgageInterestRate->mortgage_id   = $mortgageId;
            $mortgageInterestRate->term_start    = $termStart2;
            $mortgageInterestRate->term_end      = $this->termEnd2;
            $mortgageInterestRate->interest_rate = preg_replace("/[^0-9.]/", "", $primePlus);
            $mortgageInterestRate->new_term      = 'no';
            $mortgageInterestRate->created_by    = $userId;
            $mortgageInterestRate->updated_by    = $userId;
            $mortgageInterestRate->save();
        }
    }

    public function createPmtsFromCheques($savedQuoteId, $mortgageId, $firstChequeDate, $lastChequeDate, $firstChequeAmt, $regularChequeAmt) {
        $this->logger->info('InitializationBO->createPmtsFromCheques',[$savedQuoteId, $mortgageId, $firstChequeDate, $lastChequeDate, $firstChequeAmt, $regularChequeAmt]);

        $userId = Auth::user()->user_id;

        if($firstChequeDate == '' || $lastChequeDate == '' || $firstChequeDate == '0000-00-00' || $lastChequeDate == '0000-00-00') {
            return true;
        } else {
            $df = new Datetime($firstChequeDate);
            $dl = new DateTime($lastChequeDate);
        }

        $savedQuoteMissingChequesTable = SavedQuoteMissingChequesTable::query()
        ->where('saved_quote_id', $savedQuoteId)
        ->get();

        //missing cheques
        $missing = array();
        foreach($savedQuoteMissingChequesTable as $key => $value) {
            $missing[] = $value;
        }

        $cheques = array();

        while($df <= $dl) {
            if(!in_array($df, $missing)) {
                $cheques[] = $df->format('Y-m-d');
            }
            $df->modify('+1 month');
        }   

        $firstChequeTmp = new DateTime($firstChequeDate);
        $firstChequeTmp = $firstChequeTmp->format('Y-m-d');

        foreach($cheques as $idx => $date) {
            if($date == $firstChequeTmp) {
                $pmtAmt = $firstChequeAmt;
            } else {
                if ($this->piece == '') {
                    $pmtAmt = $regularChequeAmt;
                } else {
                    $pmtAmt = $this->paymentInv;
                }
            }

            $lastPaymentId = $this->getLastPaymentId($mortgageId);
            
            $mortgagePayment = new MortgagePaymentsTable;
            $mortgagePayment->payment_id           = $lastPaymentId;
            $mortgagePayment->mortgage_id          = $mortgageId;
            $mortgagePayment->original_date        = $date;
            $mortgagePayment->processing_date      = $date;
            $mortgagePayment->period_date          = $date;
            $mortgagePayment->pmt_amt              = $pmtAmt;
            $mortgagePayment->is_nsf               = 'no';
            $mortgagePayment->nsf_id               = 0;
            $mortgagePayment->is_post_dated_cheque = 'yes';
            $mortgagePayment->is_processed         = 'no';
            $mortgagePayment->is_add_on_fee        = 'no';
            $mortgagePayment->comment              = '';
            $mortgagePayment->created_by           = $userId;
            $mortgagePayment->save();           
        }        
    }

    public function createEntriesMortgagePropertiesTable($savedQuoteId,$mortgageId) {

        $query = 'Select b.position, c.* 
                  From saved_quote_table a
                  Join saved_quote_positions_table b on b.saved_quote_id = a.saved_quote_id 
                  Join property_table c on c.application_id = a.application_id and c.idx = b.idx 
                  Where a.saved_quote_id = ?';
        $res = $this->db->select($query, [$savedQuoteId]);

        foreach ($res as $key => $value) {
            if ($value->position != 'N/A') {
                $mPropertyTable = new MortgagePropertiesTable;
                $mPropertyTable->mortgage_id     = $mortgageId;
                $mPropertyTable->property_id     = $value->property_id;
                $mPropertyTable->position        = $value->position;
                $mPropertyTable->alpine_interest = $value->alpine_interest;
                $mPropertyTable->save();
            }
        }
    }

    public function insertMortgagePaymentsTable($mortgageId, $IntCommDate, $periodDate, $gross) {

        $userId = Auth::user()->user_id;

        $this->logger->info('InitializationBO->insertMortgagePaymentsTable',[$mortgageId,$IntCommDate,$periodDate,$gross]);

        if ($this->piece == '') {
            $pmtAmt = $gross;
        } else {
            $pmtAmt = $this->grossInv;
        }

        $lastPaymentId = $this->getLastPaymentId($mortgageId);

        $mortgagePayment = new MortgagePaymentsTable();
        $mortgagePayment->payment_id      = $lastPaymentId;
        $mortgagePayment->mortgage_id     = $mortgageId;
        $mortgagePayment->original_date   = $IntCommDate;
        $mortgagePayment->processing_date = $IntCommDate;
        //$mortgagePayment->period_date     = $periodDate;
        $mortgagePayment->period_date     = new DateTime(); //requested by Alpine agents
        $mortgagePayment->pmt_amt         = '-'.$pmtAmt;
        $mortgagePayment->is_nsf          = 'no';
        $mortgagePayment->nsf_id          = 0;
        $mortgagePayment->is_post_dated_cheque = 'no';
        $mortgagePayment->is_processed    = 'yes';
        $mortgagePayment->is_add_on_fee   = 'no';
        $mortgagePayment->comment         = '';
        $mortgagePayment->initial_pmt     = 'yes';
        $mortgagePayment->created_by      = $userId;
        $mortgagePayment->save();
    }

    public function getperiodLocksTable($savedQuoteIntDate) {

        $this->logger->info('InitializationBO->getperiodLocksTable ',[$savedQuoteIntDate]);
        
        $periodLocksTable = PeriodLocksTable::query()
        ->where('company_id', $this->companyId)
        ->where('start_date', '<=', $savedQuoteIntDate) 
        ->where('start_date', '>=', $savedQuoteIntDate)
        ->where('locked', 'yes')
        ->get();

        return $periodLocksTable;
    }

    public function getLastPaymentId($mortgageId) {
        
        $this->logger->info('InitializationBO->getLastPaymentId ',[$mortgageId]);

        $lastPaymentId = 0;

        $query = 'select MAX(payment_id) AS last_payment_id FROM mortgage_payments_table WHERE mortgage_id = ?';
        $res = $this->db->select($query, [$mortgageId]);

        if(count($res) > 0) {
            $lastPaymentId = $res[0]->last_payment_id;
        }

        $lastPaymentId += 1;

        return $lastPaymentId;
    }

    public function checkDeletedMortgage($applicationId) {

        $this->logger->info('InitializationBO->checkDeletedMortgage ',[$applicationId]);

        $num1 = 0;

        $query = 'select count(*) num1 from mortgage_table where is_deleted= "no" and application_id = ?';
        $res = $this->db->select($query, [$applicationId]);

        if ($res) {
            if (isset($res[0]->num1)) {
                $num1 = $res[0]->num1;
            }
        }

        return $num1;
    }

    public function checkPbPool($agent) {

        $this->logger->info('InitializationBO->checkPbPool ',[$agent]);

        $num2 = 0;

        $query = 'select count(*) num2 from pb_pool where user_id = ?';
        $res = $this->db->select($query, [$agent]);

        if ($res) {
            if (isset($res[0]->num2)) {
                $num2 = $res[0]->num2;
            }
            
        }

        return $num2;
    }

    public function createMortgageNsf($mortgageId,$nsfFee,$intCommDate) {

        $mortgageNsfFee = new MortgageNsfFee;
        $mortgageNsfFee->mortgage_id    = $mortgageId;        
        $mortgageNsfFee->nsf_fee_amount = $nsfFee;
        $mortgageNsfFee->effective_at   = $intCommDate;
        
        $mortgageNsfFee->save();
    }

    public function directFunded($savedQuoteId)  {

        $investorIds = [31, 100, 248];

        $result = SaleInvestorTable::query()
        ->where('saved_quote_id', $savedQuoteId)
        ->whereIn('investor_id', $investorIds)
        ->where('fm_committed', 'Yes')
        ->first();

        if ($result) {
            $directFunded = 'Yes';
        } else {
            $directFunded = 'No';
        }

        return $directFunded;
    }

    public function getApplicationTable($applicationId) {

        $applicationTable = ApplicationTable::query()
        ->where('application_id', $applicationId)
        ->first();

        return $applicationTable;
    }

    public function getSourcesTable() {

        $sourcesTable = SourcesTable::query()
        ->where('B2B_admin', 'yes')
        ->get();

        return $sourcesTable;
    }

    public function getSaleInvestorTable($savedQuoteId) {

        $saleInvestorTable = SaleInvestorTable::query()
        ->where('saved_quote_id', $savedQuoteId)
        ->where('investor_id', '1971')
        ->where('fm_committed', 'Yes')
        ->first();

        return $saleInvestorTable;
    }

    public function getSavedQuoteTable($savedQuoteId) {

        $savedQuoteTable = SavedQuoteTable::query()
        ->where('saved_quote_id', $savedQuoteId)
        ->first();

        return $savedQuoteTable;

    }

    public function getApplicantsName($applicationId) {

        $applicantsName = array();

        $query = 'Select a.application_id, a.applicant_id, b.spouse_id, b.f_name, b.m_name, b.l_name, b.type 
                  From applicant_table a
                  Join spouse_table b on b.spouse_id = a.spouse1_id or b.spouse_id = spouse2_id
                  Where a.application_id = ?';
        $applicantTable = $this->db->select($query, [$applicationId]);

        foreach($applicantTable as $key => $value) {
            $fullName = '';

            if ($value->f_name != '' && $value->l_name != '') {
                $fullName = $value->f_name;

                if ($value->m_name != '') {
                    $fullName .= ' ' . $value->m_name;
                }

                $fullName .= ' ' . $value->l_name;

                $applicantsName[] = [
                    'applicantId'   => $value->applicant_id,
                    'spouseId'      => $value->spouse_id,
                    'corporationId' => 0,
                    'name'          => $fullName,
                    'type'          => $value->type
                ];
            }
        }
            
        $corporationTable = CorporationTable::query()
        ->where('application_id', $applicationId)
        ->get();

        if ($corporationTable) {
            foreach ($corporationTable as $key => $value) {
                $applicantsName[] = [
                    'applicantId'   => 0,
                    'spouseId'      => 0,
                    'corporationId' => $value->corporation_id,
                    'name'          => $value->name,
                    'type'          => 0
                ];
            }
        }

        return $applicantsName;
    }

    public function getInvestorData($savedQuoteId) {

        $saleInvestorTable = SaleInvestorTable::query()
        ->where('investor_id', 1971)
        ->where('saved_quote_id', $savedQuoteId)
        ->first();

        if ($saleInvestorTable) {
            $this->investorData = $saleInvestorTable;
        }
    }

    public function createPiece() {

        if ($this->investorData) {

            $coMic = array(1 => 0, 5 => 31, 16 => 248, 182 => 100, 1970 => 1971, 200 => 1993, 201 => 1991, 202 => 1992);
            $external = array(200,201,202);    

            if ($this->piece == 'ap') {
    
                $this->investorIdInv  = empty($this->investorData->ap_priv_inv_id) ? $coMic[$this->investorData->ap_inv_co] : $this->investorData->ap_priv_inv_id;
                $this->companyIdInv   = empty($this->investorData->ap_inv_co) ? 1 : $this->investorData->ap_inv_co;
                $this->grossInv       = $this->investorData->ap_amount;
                $this->rateInv        = $this->investorData->ap_rate;
                $this->paymentInv     = $this->investorData->ap_payment;
                $this->discountInv    = $this->investorData->ap_discount;
                if (empty($this->discountInv)) {
                    $this->discountInv = 0;
                }
                $this->priceInv    = $this->investorData->ap_price;
                $this->yieldInv    = $this->investorData->ap_yield;
                $this->internalInv = (in_array($this->investorData->ap_inv_co,$external)) ? 'No' : 'Yes';
    
            } elseif ($this->piece == 'bp') {
    
                $this->investorIdInv  = empty($this->investorData->bp_priv_inv_id) ? $coMic[$this->investorData->bp_inv_co] : $this->investorData->bp_priv_inv_id;
                $this->companyIdInv   = empty($this->investorData->bp_inv_co) ? 1 : $this->investorData->bp_inv_co;
                $this->grossInv       = $this->investorData->bp_amount;
                $this->rateInv        = $this->investorData->bp_rate;
                $this->paymentInv     = $this->investorData->bp_payment;
                $this->discountInv    = $this->investorData->bp_discount;
                if (empty($this->discountInv)) {
                    $this->discountInv = 0;
                }
                $this->priceInv    = $this->investorData->bp_price;
                $this->yieldInv    = $this->investorData->bp_yield;
                $this->internalInv = (in_array($this->investorData->bp_inv_co,$external)) ? 'No' : 'Yes';
    
            } elseif ($this->piece == 'cp') {
    
                $this->investorIdInv = empty($this->investorData->cp_priv_inv_id) ? $coMic[$this->investorData->cp_inv_co] : $this->investorData->cp_priv_inv_id;
                $this->companyIdInv  = empty($this->investorData->cp_inv_co) ? 1 : $this->investorData->cp_inv_co;
                $this->grossInv      = $this->investorData->cp_amount;
                $this->rateInv       = $this->investorData->cp_rate;
                $this->paymentInv    = $this->investorData->cp_payment;
                $this->discountInv   = $this->investorData->cp_discount;
                if (empty($this->discountInv)) {
                    $this->discountInv = 0;
                }
                $this->priceInv    = $this->investorData->cp_price;
                $this->yieldInv    = $this->investorData->cp_yield;
                $this->internalInv = (in_array($this->investorData->cp_inv_co,$external)) ? 'No' : 'Yes';
            }
        }
    }

    public function getUserId($userIdSf) {

        $userId = 0;

        if(isset($userIdSf) && !is_null($userIdSf)) {

            $sfi = new SalesforceIntegration($this->db, $this->logger);
            if($sfi->getBySalesforceId('User', $userIdSf)) {
                $userId = $sfi->getObjectId();
            }
        }

        $this->logger->info('InitializationBO->getUserId ',[$userId]);

        return $userId;
    }

    public function getInstitutionId($institution) {

        $institutionId = 0;

        $papInstitution = PapInstitution::query()
        ->where('code', $institution)
        ->first();

        if ($papInstitution) {
            $institutionId = $papInstitution->id;
        }

        return $institutionId;
    }

    public function getReferringAgent($applicationId) {

        $applicationTable = ApplicationTable::query()
        ->where('application_id', $applicationId)
        ->first();

        $referringAgent = null;

        if ($applicationTable) {
            if ($applicationTable->nb_referring_agent_id > 0 && ($applicationTable->pb_referring_agent_id == null || 
                                                                 $applicationTable->pb_referring_agent_id == ''   ||
                                                                 $applicationTable->pb_referring_agent_id == 0)) {
                $referringAgent = $applicationTable->nb_referring_agent_id;
            }elseif ($applicationTable->pb_referring_agent_id > 0) {
                $referringAgent = $applicationTable->pb_referring_agent_id;
            }
        }

        return $referringAgent;

    }

}
