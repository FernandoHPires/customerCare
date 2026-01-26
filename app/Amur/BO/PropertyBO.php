<?php

namespace App\Amur\BO;

use App\Amur\Bean\DB;
use App\Amur\Bean\ILogger;
use App\Models\PropertyTable;
use App\Models\PropertyRentalsTable;
use App\Models\PropertyMortgagesTable;
use App\Models\StreetsTable;
use App\Models\DirectionsTable;
use App\Models\ValueMethodsTable;
use App\Models\OrderMethodsTable;
use App\Models\WhoWillPayTable;
use App\Models\PropertyTypesTable;
use App\Models\UnitTypesTable;
use App\Models\BasementTable;
use App\Models\HeatTable;
use App\Models\RoofingTable;
use App\Models\ExteriorFinishTable;
use App\Models\HouseStyleTable;
use App\Models\WaterSourceTable;
use App\Models\SewageTable;
use App\Models\RentalTypesTable;
use App\Models\ApplicantTypesTable;
use App\Models\MaritalStatusTable;
use App\Amur\Utilities\ConvertDate;
use App\Amur\Utilities\Utils;
use App\Amur\Utilities\HttpRequest;
use App\Models\AppraisalUpdate;
use App\Models\AppraisalFirmsTable;
use Illuminate\Support\Facades\Auth;
use App\Amur\BO\BrokerFirmBO;
use App\Amur\Utilities\Notification;
use App\Models\GroupMembersTable;
use DateTime;

class PropertyBO {

    private $logger;
    private $db;
    private $masterOptions = array();
    private $permArray = array();

    public function __construct(ILogger $logger, DB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function index() {}

    public function getDataByApplicationId($applicationId) {

        $this->getMasterOptions($applicationId);

        $data = PropertyTable::query()
            ->where('application_id', $applicationId)
            ->orderBy('idx')
            ->orderBy('property_id')
            ->get();

        $propertyOrder = 0;

        $properties = array();
        foreach ($data as $key => $value) {

            $propertyOrder++;

            $properytyMortgages = $this->getPropertyMortgagesByPopertyId($value->property_id);
            $propertyRental = $this->getPropertyRentalByPopertyId($value);
            $appraisalUpdate = $this->getAppraisalDate($value->property_id);

            $updateInProgress = false;
            $required = null;

            if($appraisalUpdate['stage'] == 2) {
                $updateInProgress = true;
            }

            if(isset($appraisalUpdate['required']) && $appraisalUpdate['required'] != null) {
                $required = (new DateTime($appraisalUpdate['required']))->format('m/d/Y');
            }

            $brokerFirmBO = new BrokerFirmBO($this->logger, $this->db);
            $insBrokerName = $brokerFirmBO->getBrokerFirmName($value->ins_broker_id);
            
            $mortgageBO = new MortgageBO($this->logger, $this->db);
            //$seniorMortgages = $mortgageBO->getExistingMortgages($value->property_id);
            $inHouseMortgages = $mortgageBO->getInHouseMortgages($value->property_id);

            $properties[] = [
                "id" => $value->property_id,
                "titleHolders" => $value->title_holders,
                "type" => $value->type,
                "taxes" => $value->ppty_tax,
                "arrearsUtd" => $value->ppty_tax_arrears,
                "insBrokerId" => $value->ins_broker_id,
                "insBrokerName" => $insBrokerName,
                'insArrears' => $value->ins_arrears,
                "insExpireDate" => $value->ins_expiry,
                "alpineInterest" => $value->alpine_interest,
                "unitNumber" => $value->unit_number,
                "unitType" => $value->unit_type,
                "streetNumber" => $value->street_number,
                "streetName" => $value->street_name,
                "streetType" => $value->street_type,
                "direction" => $value->street_direction,
                "city" => $value->city,
                "province" => $value->province,
                "postalCode" => $value->postal_code,
                "fullAddress" => Utils::oneLineAddress($value->unit_number, $value->street_number, $value->street_name, $value->street_type, $value->street_direction, $value->city, $value->province, $value->postal_code),
                "addressNoZip" => Utils::oneLineAddress($value->unit_number, $value->street_number, $value->street_name, $value->street_type, $value->street_direction, $value->city, $value->province, ''),
                "pid" => $value->pid,
                "legal" => html_entity_decode($value->legal),
                "customerValue" => $value->customer_value,
                "assessedValue" => $value->assessed_value,
                "landAssdValue" => $value->land_value,
                "buildingAssdValue" => $value->building_value,
                "numberOfYears" => $value->number_of_years,
                "costPrice" => $value->cost_price,
                "downpayment" => $value->downpayment,
                "ownRent" => $value->own_rent,
                "partOfSecurity" => $value->part_of_security,
                "appraisersFirmCode" => $value->appraisal_firm_id,
                "appraisalFirmName" => $this->getAppraisersFirmName($value->appraisal_firm_id),
                "appraiserCode" => $value->appraiser_id,
                "appraisalReceived" => $value->appraisal_recvd,
                "appraisedValue" => $value->appraised_value,
                "estimateValue" => $value->estimate_value,
                "valueMethod" => $value->value_method,
                "orderMethod" => $value->appraisal_order_method,
                "recentPropValue" => $value->recent_value,
                "recentPropType" => $value->recent_value_type,
                "recentPropDate" => $value->recent_value_date,
                "whoWillPay" => $value->appraisal_payer,
                "hasMarketValuationAccess" => $this->hasMarketValuationAccess(Auth::user()->user_id),
                "marketValuationConfidence" => $value->market_valuation_confidence,
                "marketValuationAmount" => $value->market_valuation_amount,
                "marketValuationDate" => $value->market_valuation_date,
                "estimatedLowRangeValue" => $value->estimated_low_range_value,
                "estimatedHighRangeValue" => $value->estimated_high_range_value,
                "estimatedRangeDate" => $value->estimated_range_value_date,
                "estimatedMidRangeValue" => $value->estimated_low_range_value + (($value->estimated_high_range_value - $value->estimated_low_range_value) / 2),
                "sameAsMailing" => $value->same_as_mailing,
                "ruralUrban" => $value->rural_urban,
                "appraisalDateOrdered" => $value->appraisal_date_ordered,
                "appraisalDateReceived" => $value->appraisal_date_received,
                "yearBuilt" => $value->built,
                "lotSize" => $value->lot_size,
                "floorArea" => $value->floor_area,
                "floorMeasurement" => $value->floor_area_units,
                "basement" => $value->basement,
                "bedrooms" => $value->bedrooms,
                "bathrooms" => $value->bathrooms,
                "heat" => $value->heat,
                "roofing" => $value->roofing,
                "exteriorFinish" => $value->exterior_finishing,
                "houseStyle" => $value->house_style,
                "garage" => $value->garage,
                "outBuilding" => $value->out_building,
                "waterSource" => $value->water_source,
                "sewage" => $value->sewage,
                "comments" => $value->comments,
                "heatingCost" => $value->heating_cost,
                "appraisalUpdate" => $updateInProgress,
                "strata" => $value->strata_fee,
                "strataArrears" => $value->strata_arrears,
                "strataCompany" => $value->strata_company,
                "strataContact" => $value->strata_contact,
                "strataPhone" => $value->strata_phone,
                "strataFax" => $value->strata_fax,
                "strataEmail" => $value->strata_email,
                "strataOther" => $value->strata_other,
                "propertyMortgages" => $properytyMortgages,
                "inHouseMortgages" => $inHouseMortgages,
                "propertyRentals" => $propertyRental,
                "idx" => $value->idx,
                "isRemoved" => false,
                'propertyOrder' => $propertyOrder,
                'required' => $required
            ];
        }

        return $properties;
    }

    public function getPropertyMortgagesByPopertyId($propertyId) {

        $sql = "select a.*, c.firm_name lender_name
                  from property_mortgages_table a
             left join lender_firm_branches_table b on b.lender_branch_code = a.lender_id
             left join lender_firm_table c on b.lender_code = c.lender_code
                 where a.property_id = ?
              order by a.mortgage_id";
        $res = $this->db->select($sql, [$propertyId]);

        $propertyMortgages = array();
        foreach($res as $key => $value) {
            $propertyMortgages[] = [
                "id" => $value->mortgage_id,
                "mortgageBalance" => $value->balance,
                "balanceDate" => $value->balance_date,
                "payment" => $value->payment,
                "paymentType" => $value->payment_type,
                "pit" => $value->pit,
                "mtgeIns" => $value->floating_charge,
                "runningAccount" => $value->running_account,
                "lineOfCredit" => $value->line_of_credit,
                "toBePaidOut" => $value->payout,
                "term" => $value->term,
                "rate" => $value->rate,
                "lenderCode" => $value->lender_id,
                "lenderName" => $value->lender_name,
                "arrearsUtd" => $value->arrears,
                "solicitAtTerm" => $value->solicit,
                "softConfirmed" => $value->soft_confirmed,
                "finalConfirmed" => $value->confirmed,
                "deferredConfirmed" => $value->deferred_confirmed,
                "deferredAmount" => $value->deferred_amount,
                "createdBy" => $this->getInitials($value->created_by),
                "createdAt" => $value->created_at,
                "updatedBy" => $this->getInitials($value->updated_by),
                "updatedAt" => (new DateTime($value->updated_at))->format('m/d/Y'),
                "setting" => $value->setting,
                "master" => $value->master,
                "coLenderRank" => $value->co_lender_rank,
                "isRemoved" => false,
                "masterOptions" => $this->masterOptions
            ];
        }

        return $propertyMortgages;
    }

    public function getInitials($userId) {
        $query = "SELECT users_table.user_fname, users_table.user_lname
                    FROM users_table
                   where users_table.user_id = ?";
        $res = $this->db->select($query, [$userId]);

        if(!empty($res) && isset($res[0]->user_fname)) {
            $initials = substr($res[0]->user_fname, 0, 1);
            if(isset($res[0]->user_lname) && !empty($res[0]->user_lname)) {
                $initials .= substr($res[0]->user_lname, 0, 1);
            }

            return $initials;
        }

        return '';
    }

    public function getPropertyRentalByPopertyId($property) {

        $propertyRental = array();

        $data = PropertyRentalsTable::query()
            ->where('property_id', $property->property_id)
            ->get();

        foreach ($data as $key => $value) {
            $propertyRental[] = [
                'id' => $value->rental_id,
                'type' => $value->type,
                'property' => Utils::oneLineAddress($property->unit_number, $property->street_number, $property->street_name, $property->street_type, $property->street_direction, $property->city, $property->province, $property->postal_code),
                'monthlyIncome' => $value->income,
                'isRemoved' => false
            ];
        }

        return $propertyRental;
    }

    public function updatePropertyMortgagesById($id = null, $propertyId = null, $mortgage = []) {
        try {
            if ($id == null || $id == "") {
                //insert
                $mortgageInsert = new PropertyMortgagesTable;
                $mortgageInsert->property_id = $propertyId;
                $mortgageInsert->arrears = isset($mortgage['arrearsUtd']) ? $mortgage['arrearsUtd'] : "";
                $mortgageInsert->balance = isset($mortgage['mortgageBalance']) ? floatval($mortgage['mortgageBalance']) : 0;
                $mortgageInsert->payment = isset($mortgage['payment']) ? floatval($mortgage['payment']) : 0;
                $mortgageInsert->payment_type = isset($mortgage['paymentType']) ? $mortgage['paymentType'] : "";
                $mortgageInsert->pit = isset($mortgage['pit']) ? $mortgage['pit'] : "";
                $mortgageInsert->floating_charge = isset($mortgage['mtgeIns']) ? $mortgage['mtgeIns'] : "";
                $mortgageInsert->running_account = isset($mortgage['runningAccount']) ? $mortgage['runningAccount'] : "";
                $mortgageInsert->payout = isset($mortgage['toBePaidOut']) ? $mortgage['toBePaidOut'] : "";
                $mortgageInsert->rate = isset($mortgage['rate']) ? $mortgage['rate'] : 0;
                $mortgageInsert->lender_id = isset($mortgage['lenderCode']) ? $mortgage['lenderCode'] : 0;
                $mortgageInsert->solicit = isset($mortgage['solicitAtTerm']) ? $mortgage['solicitAtTerm'] : "";
                $mortgageInsert->soft_confirmed = isset($mortgage['softConfirmed']) ? $mortgage['softConfirmed'] : "";
                $mortgageInsert->confirmed = isset($mortgage['finalConfirmed']) ? $mortgage['finalConfirmed'] : "";
                $mortgageInsert->deferred_confirmed = isset($mortgage['deferredConfirmed']) ? $mortgage['deferredConfirmed'] : "";
                $mortgageInsert->deferred_amount = isset($mortgage['deferredAmount']) ? $mortgage['deferredAmount'] : "";
                $mortgageInsert->setting = isset($mortgage['interalia']) ? $mortgage['interalia'] : "";
                $mortgageInsert->master = isset($mortgage['master']) ? $mortgage['master'] : "";
                $mortgageInsert->position = "";

                if (isset($mortgage['balanceDate'])) {
                    $balanceDate = ConvertDate::convert($mortgage['balanceDate']);
                    if ($balanceDate) {
                        $mortgageInsert->balance_date = $balanceDate;
                    } else {
                        $mortgageInsert->balance_date = null;
                    }
                } else {
                    $mortgageInsert->balance_date = null;
                }

                if (isset($mortgage['term'])) {
                    $term = ConvertDate::convert($mortgage['term']);
                    if ($term) {
                        $mortgageInsert->term = $mortgage['term'];
                    } else {
                        $mortgageInsert->term = null;
                    }
                } else {
                    $mortgageInsert->term = null;
                }

                // save data
                $mortgageInsert->save();
            } else {
                //update
                $mortgageInfo = [
                    "arrears" => isset($mortgage['arrearsUtd']) ? $mortgage['arrearsUtd'] : "",
                    "balance" => isset($mortgage['mortgageBalance']) ? floatval($mortgage['mortgageBalance']) : 0,
                    "payment" => isset($mortgage['payment']) ? floatval($mortgage['payment']) : 0,
                    "payment_type" => isset($mortgage['paymentType']) ? $mortgage['paymentType'] : "",
                    "pit" => isset($mortgage['pit']) ? $mortgage['pit'] : "",
                    "floating_charge" => isset($mortgage['mtgeIns']) ? $mortgage['mtgeIns'] : "",
                    "running_account" => isset($mortgage['runningAccount']) ? $mortgage['runningAccount'] : "",
                    "payout" => isset($mortgage['toBePaidOut']) ? $mortgage['toBePaidOut'] : "",
                    "rate" => isset($mortgage['rate']) ? $mortgage['rate'] : 0,
                    "lender_id" => isset($mortgage['lenderCode']) ? $mortgage['lenderCode'] : 0,
                    "solicit" => isset($mortgage['solicitAtTerm']) ? $mortgage['solicitAtTerm'] : "",
                    "soft_confirmed" => isset($mortgage['softConfirmed']) ? $mortgage['softConfirmed'] : "",
                    "confirmed" => isset($mortgage['finalConfirmed']) ? $mortgage['finalConfirmed'] : "",
                    "deferred_confirmed" => isset($mortgage['deferredConfirmed']) ? $mortgage['deferredConfirmed'] : "",
                    "deferred_amount" => isset($mortgage['deferredAmount']) ? $mortgage['deferredAmount'] : "",
                    "setting" => isset($mortgage['interalia']) ? $mortgage['interalia'] : "",
                    "master" => isset($mortgage['master']) ? $mortgage['master'] : ""
                ];

                if (isset($mortgage['balanceDate'])) {
                    $balanceDate = ConvertDate::convert($mortgage['balanceDate']);
                    if ($balanceDate) {
                        $mortgageInfo['balance_date'] = $balanceDate;
                    } else {
                        $mortgageInfo['balance_date'] = null;
                    }
                } else {
                    $mortgageInfo['balance_date'] = null;
                }

                if (isset($mortgage['term'])) {
                    $term = ConvertDate::convert($mortgage['term']);
                    if ($balanceDate) {
                        $mortgageInfo['term'] = $term;
                    } else {
                        $mortgageInfo['term'] = null;
                    }
                } else {
                    $mortgageInfo['term'] = null;
                }

                $data = PropertyMortgagesTable::query()
                    ->where('mortgage_id', $id)
                    ->update($mortgageInfo);

                if (!$data) return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->updatePropertyMortgagesById - Update Error', [json_encode($e)]);
            return false;
        }
    }

    public function getTitleHolders($applicationId) {

        $this->permArray = array();

        $query = "select b.f_name, b.l_name
                    from applicant_table a 
                    join spouse_table b on b.spouse_id = a.spouse1_id or b.spouse_id = a.spouse2_id
                   where a.application_id = ?
                     and b.f_name <> ''
                order by b.spouse_id";
        $res = $this->db->select($query, [$applicationId]);

        $array = array();
        foreach ($res as $key => $value) {
            $array[] = $value->f_name . ' ' . $value->l_name . ', ';
        }

        $query = 'select name 
                    from corporation_table 
                   where application_id = ?
                order by corporation_id
                   limit 5';
        $res = $this->db->select($query, [$applicationId]);

        foreach ($res as $key => $value) {
            if (!is_null($value->name && !empty($value->name))) {
                $array[] = $value->name . ', ';
            }
        }

        $len = sizeof($array);
        for ($i = 0; $i < $len; $i++) {
            $this->perm($array[$i], $i, $array);
        }

        $this->mergesort($this->permArray, "strcmp");
        $this->mergesort($this->permArray, "cmp");

        for ($i = 0; $i < sizeof($this->permArray); $i++) {
            $this->permArray[$i] = trim($this->permArray[$i]);
            $this->permArray[$i] = substr($this->permArray[$i], 0, strlen($this->permArray[$i]) - 1);
        }

        $titleHolders = array();
        foreach ($this->permArray as $key => $value) {
            $titleHolders[] = [
                'id' => $value,
                'name' => $value
            ];
        }
        return $titleHolders;
    }

    public function perm($str, $index, &$elementArray) {
        if ($index < 0) {
            return;
        }

        $this->permArray[] = $str;
        for ($j = $index; $j - 1 >= 0; $j--) {
            $nextPerm = $elementArray[$j - 1] . $str . " ";
            $this->perm($nextPerm, $j - 1, $elementArray);
        }
    }

    public function mergesort(&$array, $cmp_function) {
        // Arrays of size < 2 require no action.
        if (count($array) < 2) return;

        // Split the array in half
        $halfway = count($array) / 2;
        $array1  = array_slice($array, 0, $halfway);
        $array2  = array_slice($array, $halfway);

        // Recurse to sort the two halves
        $this->mergesort($array1, $cmp_function);
        $this->mergesort($array2, $cmp_function);


        if ($cmp_function == 'cmp') {
            if ($this->cmp(end($array1), $array2[0]) < 1) {
                $array = array_merge($array1, $array2);
                return;
            }
        } else {
            // If all of $array1 is <= all of $array2, just append them.
            if (call_user_func($cmp_function, end($array1), $array2[0]) < 1) {
                $array = array_merge($array1, $array2);
                return;
            }
        }

        // Merge the two sorted arrays into a single sorted array
        $array = array();
        $ptr1  = $ptr2 = 0;

        while ($ptr1 < count($array1) && $ptr2 < count($array2)) {
            if ($cmp_function == 'cmp') {
                if ($this->cmp($array1[$ptr1], $array2[$ptr2]) < 1) {
                    $array[] = $array1[$ptr1++];
                } else {
                    $array[] = $array2[$ptr2++];
                }
            } else {
                if (call_user_func($cmp_function, $array1[$ptr1], $array2[$ptr2]) < 1) {
                    $array[] = $array1[$ptr1++];
                } else {
                    $array[] = $array2[$ptr2++];
                }
            }
        }

        // Merge the remainder
        while ($ptr1 < count($array1)) $array[] = $array1[$ptr1++];
        while ($ptr2 < count($array2)) $array[] = $array2[$ptr2++];

        return;
    }

    public function cmp($a, $b) {
        $num_a = substr_count($a, " ");
        $num_b = substr_count($b, " ");

        if ($num_a == $num_b) {
            return 0;
        }

        return ($num_a < $num_b) ? -1 : 1;
    }

    public function storeProperty($properties, $applicationId) {

        $this->db->beginTransaction();
        try {
            foreach ($properties as $key => $property) {

                if (isset($property['isRemoved']) && $property['isRemoved']) {

                    PropertyTable::query()
                        ->where('property_id', $property['id'])
                        ->update(['application_id' => 0]);

                    foreach ($property['propertyMortgages'] as $data) {
                        PropertyMortgagesTable::query()
                            ->where('mortgage_id', $data['id'])
                            ->delete();
                    }

                    foreach ($property['propertyRentals'] as $data) {
                        PropertyRentalsTable::query()
                            ->where('rental_id', $data['id'])
                            ->delete();
                    }
                } else {
                    $propertyObj = PropertyTable::find($property['id']);

                    if (!$propertyObj) {
                        $idx = PropertyTable::query()
                            ->where('application_id', $applicationId)
                            ->max('idx');

                        $propertyObj = new PropertyTable();
                        $propertyObj->application_id = $applicationId;
                        $propertyObj->idx = $idx + 1;
                    }

                    $propertyObj->title_holders = $property['titleHolders'] ?? '';
                    $propertyObj->type = $property['type'] ?? 'Residence';
                    $propertyObj->alpine_interest = $property['alpineInterest'] ?? 0;
                    $propertyObj->ppty_tax = $property['taxes'] ?? 0;
                    $propertyObj->ppty_tax_arrears = $property['arrearsUtd'] ?? 0;
                    $propertyObj->ins_broker_id = $property['insBrokerId'] ?? 0;
                    $propertyObj->ins_arrears = $property['insArrears'] ?? '';
                    $propertyObj->ins_expiry = $property['insExpireDate'] ?? '';
                    $propertyObj->unit_number = $property['unitNumber'] ?? '';
                    $propertyObj->unit_type = $property['unitType'] ?? '';
                    $propertyObj->street_number = $property['streetNumber'] ?? '';
                    $propertyObj->street_name = $property['streetName'] ?? '';
                    $propertyObj->street_type = $property['streetType'] ?? '';
                    $propertyObj->street_direction = $property['direction'] ?? '';
                    $propertyObj->city = $property['city'] ?? '';
                    $propertyObj->province = $property['province'] ?? '';
                    $propertyObj->postal_code = $property['postalCode'] ?? '';
                    $propertyObj->pid = $property['pid'] ?? '';
                    $propertyObj->legal = $property['legal'] ?? '';
                    $propertyObj->customer_value = $property['customerValue'] ?? 0;
                    $propertyObj->assessed_value = $property['assessedValue'] ?? 0;
                    $propertyObj->land_value = $property['landAssdValue'] ?? 0;
                    $propertyObj->building_value = $property['buildingAssdValue'] ?? 0;
                    $propertyObj->number_of_years = $property['numberOfYears'] ?? 0;
                    $propertyObj->cost_price = $property['costPrice'] ?? 0;
                    $propertyObj->downpayment = $property['downpayment'] ?? 0;
                    $propertyObj->own_rent = $property['ownRent'] ?? '';
                    $propertyObj->part_of_security = $property['partOfSecurity'] ?? 'No';
                    $propertyObj->strata_fee = $property['strata'] ?? 0;
                    $propertyObj->value_method = $property['valueMethod'] ?? '';
                    $propertyObj->appraisal_firm_id = $property['appraisersFirmCode'] ?? 0;
                    $propertyObj->appraiser_id = $property['appraiserCode'] ?? 0;
                    $propertyObj->appraisal_order_method = $property['orderMethod'] ?? '';
                    $propertyObj->appraisal_payer = $property['whoWillPay'] ?? '';
                    $propertyObj->appraisal_date_ordered = $property['appraisalDateOrdered'] ?? '';
                    $propertyObj->appraisal_recvd = $property['appraisalReceived'] ?? '';
                    $propertyObj->appraisal_date_received = $property['appraisalDateReceived'] ?? '';
                    $propertyObj->appraised_value = $property['appraisedValue'] ?? 0;
                    $propertyObj->estimate_value = $property['estimateValue'] ?? 0;
                    $propertyObj->recent_value_type = $property['recentPropType'] ?? '';
                    $propertyObj->recent_value = $property['recentPropValue'] ?? 0;
                    $propertyObj->recent_value_date = $property['recentPropDate'] ?? '';
                    $propertyObj->same_as_mailing = $property['sameAsMailing'] ?? '';
                    $propertyObj->rural_urban = $property['ruralUrban'] ?? '';
                    $propertyObj->heat = $property['heat'] ?? '';
                    $propertyObj->heating_cost = $property['heatingCost'] ?? '';
                    $propertyObj->strata_fee = $property['strata'] ?? 0;
                    $propertyObj->strata_arrears = $property['strataArrears'] ?? '';
                    $propertyObj->strata_company = $property['strataCompany'] ?? '';
                    $propertyObj->strata_contact = $property['strataContact'] ?? '';
                    $propertyObj->strata_phone = $property['strataPhone'] ?? '';
                    $propertyObj->strata_fax = $property['strataFax'] ?? '';
                    $propertyObj->strata_email = $property['strataEmail'] ?? '';
                    $propertyObj->strata_other = $property['strataOther'] ?? '';
                    $propertyObj->water_source = $property['waterSource'] ?? '';
                    $propertyObj->sewage = $property['sewage'] ?? '';
                    $propertyObj->comments = $property['comments'] ?? '';
                    $propertyObj->out_building = $property['outBuilding'] ?? '';
                    $propertyObj->garage = $property['garage'] ?? '';
                    $propertyObj->house_style = $property['houseStyle'] ?? '';
                    $propertyObj->exterior_finishing = $property['exteriorFinish'] ?? '';
                    $propertyObj->floor_area = $property['floorArea'] ?? '';
                    $propertyObj->floor_area_units = $property['floorMeasurement'] ?? '';
                    $propertyObj->basement = $property['basement'] ?? '';
                    $propertyObj->bedrooms = $property['bedrooms'] ?? '';
                    $propertyObj->bathrooms = $property['bathrooms'] ?? '';
                    $propertyObj->built = $property['yearBuilt'] ?? '';
                    $propertyObj->lot_size = $property['lotSize'] ?? '';
                    $propertyObj->roofing = $property['roofing'] ?? '';
                    $propertyObj->save();

                    $this->storePropertyMortgageTable($property['propertyMortgages'], $propertyObj->property_id);
                    $this->storePropertyRentalTable($property['propertyRentals'], $propertyObj->property_id);
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('PropertyBO->storeProperty', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;
    }

    public function storePropertyMortgageTable($propertyMortgages, $propertyId) {

        $this->logger->info('PropertyBO->storePropertyMortgageTable', [$propertyId]);

        $this->db->beginTransaction();
        try {

            foreach ($propertyMortgages as $key => $propertyMtg) {

                if (isset($propertyMtg['isRemoved']) && $propertyMtg['isRemoved']) {
                    PropertyMortgagesTable::query()
                    ->where('mortgage_id', $propertyMtg['id'])
                    ->delete();
                } else {

                    $propertyMtgObj = PropertyMortgagesTable::find($propertyMtg['id']);

                    if (!$propertyMtgObj) {
                        $propertyMtgObj = new PropertyMortgagesTable();
                        $propertyMtgObj->property_id = $propertyId;
                        $propertyMtgObj->created_by = Auth::user()->user_id;
                    }

                    $propertyMtgObj->balance = $propertyMtg['mortgageBalance'] ?? 0;
                    $propertyMtgObj->balance_date = $propertyMtg['balanceDate'] ?? null;
                    $propertyMtgObj->payment = $propertyMtg['payment'] ?? 0;
                    $propertyMtgObj->payment_type = $propertyMtg['paymentType'] ?? '';
                    $propertyMtgObj->pit = $propertyMtg['pit'] ?? '';
                    $propertyMtgObj->floating_charge = $propertyMtg['mtgeIns'] ?? '';
                    $propertyMtgObj->running_account = $propertyMtg['runningAccount'] ?? '';
                    $propertyMtgObj->line_of_credit = $propertyMtg['lineOfCredit'] ?? '';
                    $propertyMtgObj->payout = $propertyMtg['toBePaidOut'] ?? '';
                    $propertyMtgObj->term = $propertyMtg['term'] ?? null;
                    $propertyMtgObj->rate = $propertyMtg['rate'] ?? 0;
                    $propertyMtgObj->lender_id = $propertyMtg['lenderCode'] ?? 0;
                    $propertyMtgObj->arrears = $propertyMtg['arrearsUtd'] ?? '';
                    $propertyMtgObj->solicit = $propertyMtg['solicitAtTerm'] ?? '';
                    $propertyMtgObj->soft_confirmed = $propertyMtg['softConfirmed'] ?? '';
                    $propertyMtgObj->confirmed = $propertyMtg['finalConfirmed'] ?? '';
                    $propertyMtgObj->deferred_confirmed = $propertyMtg['deferredConfirmed'] ?? '';
                    $propertyMtgObj->deferred_amount = $propertyMtg['deferredAmount'] ?? '';
                    $propertyMtgObj->co_lender_rank = $propertyMtg['coLenderRank'] ?? '';
                    $propertyMtgObj->setting = $propertyMtg['setting'] ?? '';
                    $propertyMtgObj->master = $propertyMtg['master'] ?? '';
                    $propertyMtgObj->updated_by = Auth::user()->user_id;
                    $propertyMtgObj->save();
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('PropertyBO->storePropertyMortgagesTable', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;
    }

    public function storePropertyRentalTable($propertyRentals, $propertyId) {

        $this->logger->info('PropertyBO->storePropertyRentalTable', [$propertyId]);

        $this->db->beginTransaction();
        try {
            foreach ($propertyRentals as $key => $propertyRental) {

                if (isset($propertyRental['isRemoved']) && $propertyRental['isRemoved']) {
                    PropertyRentalsTable::query()
                        ->where('rental_id', $propertyRental['id'])
                        ->delete();
                } else {

                    $propertyRentalObj = PropertyRentalsTable::find($propertyRental['id']);

                    if (!$propertyRentalObj) {
                        $propertyRentalObj = new PropertyRentalsTable();
                        $propertyRentalObj->property_id = $propertyId;
                    }

                    $propertyRentalObj->type = $propertyRental['type'] ?? '';
                    $propertyRentalObj->income = $propertyRental['monthlyIncome'] ?? 0;
                    $propertyRentalObj->save();
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('PropertyBO->storePropertyRentalTable', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;
    }

    public function getMasterOptions($applicationId) {

        $query = 'select a.property_id, a.idx, a.unit_number,street_number, street_name, street_type, b.balance
                  from property_table a
                  join property_mortgages_table b on a.property_id = b.property_id
                  where a.application_id = ?
                  order by a.idx, b.mortgage_id';
        $data = $this->db->select($query, [$applicationId]);

        $idxTmp = null;
        $count = 0;

        foreach ($data as $key => $value) {
            $count++;
            if ($value->idx != $idxTmp || is_null($idxTmp)) {
                $idxTmp = $value->idx;
                $count = 0;
            }

            $display = '';
            if ($value->unit_number != '') {
                $display = $value->unit_number . '-';
            }

            $display .= $value->street_number . ' ' . $value->street_name . ' ' . $value->street_type;

            if (trim($display) == '') {
                $display = $this->mortgagePosition($count + 1);
            } else {
                $display .= ' / ' . $this->mortgagePosition($count + 1);
            }

            if (!empty($value->balance)) {
                $display .= ' / ' . $value->balance;
            }

            $this->masterOptions[] = [
                'id' => $value->idx . ':' . $count,
                'name' => $display
            ];
        }
    }

    public function mortgagePosition($index) {
        $value = $index % 10;
        switch ($value) {
            case 1:
                $suffix = 'st';
                break;
            case 2:
                $suffix = 'nd';
                break;
            case 3:
                $suffix = 'rd';
                break;
            default:
                $suffix = 'th';
                break;
        }

        return $index . '' . $suffix;
    }

    public function getAppraisalDate($propertyId) {
        if($propertyId == 0) {
            return [];
        }

        $property = PropertyTable::find($propertyId);
        $applicationId = $property->application_id;

        $appraisalUpdate = AppraisalUpdate::query()
        ->where('property_id', $propertyId)
        ->orderBy('id', 'desc')
        ->first();

        if($appraisalUpdate) {

            $stage = 1;
            if(!is_null($appraisalUpdate->required) && is_null($appraisalUpdate->received) && is_null($appraisalUpdate->value)) {
                $stage = 2;
            }

            if(!is_null($appraisalUpdate->required) && !is_null($appraisalUpdate->received) && !is_null($appraisalUpdate->value)) {
                return [
                    'id' => 0,
                    'applicationId' => $applicationId,
                    'propertyId' => $propertyId,
                    'required' => (new DateTime())->format('Y-m-d'),
                    'received' => null,
                    'appraisalValue' => 0,
                    'stage' => 1,
                ];
            } else {
                return [
                    'id' => $appraisalUpdate->id,
                    'applicationId' => $applicationId,
                    'propertyId' => $appraisalUpdate->property_id,
                    'required' => $appraisalUpdate->required,
                    'received' => (!is_null($appraisalUpdate->received) ? $appraisalUpdate->received : (new DateTime())->format('Y-m-d')),
                    'appraisalValue' => $appraisalUpdate->value,
                    'stage' => $stage,
                ];
            }
        }

        return [
            'id' => 0,
            'applicationId' => $applicationId,
            'propertyId' => $propertyId,
            'required' => (new DateTime())->format('Y-m-d'),
            'received' => null,
            'appraisalValue' => 0,
            'stage' => 1,
        ];
    }

    public function saveAppraisalDate($id, $applicationId, $propertyId, $required, $received, $appraisalValue, $stage) {

        $userId = (Auth::user()->user_id ?? 99);

        $this->logger->info('PropertyBO->saveAppraisalDate', [$id, $propertyId, $required, $received, $appraisalValue, $stage, $userId]);

        $this->db->beginTransaction();
        try {
            $appraisalUpdateObj = AppraisalUpdate::find($id);

            if(!$appraisalUpdateObj) {
                $appraisalUpdateObj = new AppraisalUpdate();
                $appraisalUpdateObj->created_by = $userId;
            }

            $appraisalUpdateObj->application_id = $applicationId;
            $appraisalUpdateObj->property_id = $propertyId;
            $appraisalUpdateObj->updated_by = $userId;

            if($stage == 1) {
                $appraisalUpdateObj->required = $required;
                $appraisalUpdateObj->received = null;
                $appraisalUpdateObj->value = null;
            } else if($stage == 2) {
                $appraisalUpdateObj->received = $received;
                $appraisalUpdateObj->value = $appraisalValue;
            }

            $appraisalUpdateObj->save();

            if($stage == 2) {
                $propertyObj = PropertyTable::find($propertyId);
                $propertyObj->appraisal_date_ordered = $required;
                $propertyObj->appraisal_date_received = $received;
                $propertyObj->appraised_value = $appraisalValue;
                $propertyObj->save();

                Notification::appraisalUpdate($applicationId);
            }
        } catch (\Throwable $e) {
            $this->logger->error('PropertyBO->saveAppraisalDate', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;
    }

    public function getAppraisersFirmName($appraisalFirmId) {
        if ($appraisalFirmId > 0) {
            $appraisersFirm = AppraisalFirmsTable::find($appraisalFirmId);
            return $appraisersFirm->name ?? '';
        }
        return '';
    }

    public function getPropertyById($propertyId) {
        return PropertyTable::find($propertyId);
    }

    public function getStreetType() {
        try {
            $streetTypes = StreetsTable::orderBy('abbreviation')->get();

            return $streetTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getStreetType', [$e->getMessage()]);
            return [];
        }
    }

    public function getDirection() {
        try {
            $directionTypes = DirectionsTable::orderBy('abbreviation')->get();

            return $directionTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getDirection', [$e->getMessage()]);
            return [];
        }
    }
    
    public function getValueMethod() {
        try {
            $valueTypes = ValueMethodsTable::orderBy('name')->get();

            return $valueTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getValueMethod', [$e->getMessage()]);
            return [];
        }
    }

    public function getOrderMethod() {
        try {
            $orderTypes = OrderMethodsTable::orderBy('name')->get();

            return $orderTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getValueMethod', [$e->getMessage()]);
            return [];
        }
    }

    public function getWhoWillPay() {
        try {
            $whoWillPayTypes = WhoWillPayTable::orderBy('name')->get();

            return $whoWillPayTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getValueMethod', [$e->getMessage()]);
            return [];
        }
    }

    public function getResidentialMarketValuation($propertyId) {

        $this->logger->debug('PropertyBO->getResidentialMarketValuation', [$propertyId]);

         $userId = Auth::user()->user_id ?? 0;

        $fields = array(
            'propertyId' => $propertyId,
            'userId'     => $userId,
        );


        $amurEndpoint = env('AMUR_API_ENDPOINT');

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($amurEndpoint . '/residential-market-valuation');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->exec();

        $response = json_decode($httpRequest->getResponse());

        return $response;
    }

    public function getEstimatedValueRange($propertyId) {

        $this->logger->debug('PropertyBO->getEstimatedValueRange', [$propertyId]);

        $userId = Auth::user()->user_id ?? 0;

        $fields = array(
            'propertyId' => $propertyId,
            'userId'     => $userId,
        );

        $amurEndpoint = env('AMUR_API_ENDPOINT');

        $httpRequest = new HttpRequest($this->logger);
        $httpRequest->setUrl($amurEndpoint . '/estimated-value-range');
        $httpRequest->setMethod('post');
        $httpRequest->setContentType('json');
        $httpRequest->setAccept('json');
        $httpRequest->setFieldType('raw');
        $httpRequest->setFields(json_encode($fields));
        $httpRequest->exec();

        $response = json_decode($httpRequest->getResponse());

        return $response;
    }

    public function hasMarketValuationAccess($userId) {
        return GroupMembersTable::query()
            ->where('user_id', $userId)
            ->where('group_id', 19)
            ->where('deleted', 0)
            ->exists();
    }


    public function getProperty() {
        try {
            $propertyTypes = PropertyTypesTable::orderBy('name')->get();

            return $propertyTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getValueMethod', [$e->getMessage()]);
            return [];
        }
    }

    public function getUnitType() {
        try {
            $unitTypes = UnitTypesTable::orderBy('id')->get();

            return $unitTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getValueMethod', [$e->getMessage()]);
            return [];
        }
    }

    public function getBasement() {
        try {
            $basementTypes = BasementTable::orderBy('name')->get();

            return $basementTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getValueMethod', [$e->getMessage()]);
            return [];
        }
    }

    public function getHeat() {
        try {
            $heatTypes = HeatTable::orderBy('name')->get();

            return $heatTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getValueMethod', [$e->getMessage()]);
            return [];
        }
    }

    public function getRoofing() {
        try {
            $roofingTypes = RoofingTable::orderBy('name')->get();

            return $roofingTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getValueMethod', [$e->getMessage()]);
            return [];
        }
    }

    public function getExterior() {
        try {
            $exteriorTypes = ExteriorFinishTable::orderBy('name')->get();

            return $exteriorTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getValueMethod', [$e->getMessage()]);
            return [];
        }
    }

    public function getHouse() {
        try {
            $houseTypes = HouseStyleTable::orderBy('name')->get();

            return $houseTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getValueMethod', [$e->getMessage()]);
            return [];
        }
    }

    public function getWater() {
        try {
            $waterTypes = WaterSourceTable::orderBy('name')->get();

            return $waterTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getValueMethod', [$e->getMessage()]);
            return [];
        }
    }

    public function getSewage() {
        try {
            $sewageTypes = SewageTable::orderBy('name')->get();

            return $sewageTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getValueMethod', [$e->getMessage()]);
            return [];
        }
    }

    public function getRental() {
        try {
            $rentalTypes = RentalTypesTable::orderBy('id')->get();

            return $rentalTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getValueMethod', [$e->getMessage()]);
            return [];
        }
    }

    public function getApplicantType() {
        try {
            $applicantTypes = ApplicantTypesTable::orderBy('id')->get();

            return $applicantTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getValueMethod', [$e->getMessage()]);
            return [];
        }
    }

    public function getmaritalStatus() {
        try {
            $maritalStatusTypes = MaritalStatusTable::orderBy('id')->get();

            return $maritalStatusTypes;

        } catch (\Exception $e) {
            $this->logger->error('PropertyBO->getValueMethod', [$e->getMessage()]);
            return [];
        }
    }

    public function saveTitleHolder($applicationId, $propertyId, $titleHolders) {

        $this->logger->info('PropertyBO->saveTitleHolder', [$applicationId, $propertyId, $titleHolders]);

        if ($applicationId > 0 && $propertyId > 0) {
            return true;
        }

        $this->db->beginTransaction();

        try {

            $propertyTable = PropertyTable::find($propertyId);
            $propertyTable->title_holders = $titleHolders;
            $propertyTable->save();

        } catch (\Throwable $e) {
            $this->logger->error('PropertyBO->saveTitleHolder', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;
    }

}
