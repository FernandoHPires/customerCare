<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use App\Amur\Bean\IDB;

use App\Models\InsuranceFirmTable;
use App\Models\InsuranceFirmBranchesTable;

use App\Amur\Utilities\Utils;


class BrokerFirmBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function index() {

        $query = "select b.insurance_branch_code, i.* 
                  from insurance_firm_table i 
                  left join insurance_firm_branches_table b on i.insurance_code=b.insurance_code 
                  where b.branch_name = '_Generic_Branch_' 
                  order by firm_name asc";
        $res = $this->db->query($query);
        
        return $res;

    }

    public function brokerDetailInformation($brokerFirmCode) {
        $broker = InsuranceFirmBranchesTable::where('insurance_branch_code', $brokerFirmCode)
        ->join('insurance_firm_table', 'insurance_firm_branches_table.insurance_code', '=', 'insurance_firm_table.insurance_code')
        ->select('insurance_firm_branches_table.*', 'insurance_firm_table.firm_name')
        ->first();


        $brokerAddress =  Utils::formatAddress($broker->unit_number, $broker->street_number, $broker->street_name, $broker->street_type, $broker->street_direction, $broker->city, $broker->province, $broker->postal_code, $broker->po_box_number, $broker->station, $broker->rural_route, $broker->site, $broker->compartment);

        
        $brokerData = [
            'insuranceCode' => $broker->insurance_branch_code,
            'branchName' => $broker->branch_name,
            'firmName' => $broker->firm_name,
            'comments' => $broker->comments,
            'phone' => $broker->telephone,
            'email' => $broker->email,
            'address' => $brokerAddress
        ];

        return $brokerData;
    }

    public function addBrokerFirms($firmName, $comments) {

        $this->logger->info('BrokerFirmBO->addBrokerFirms',[$firmName, $comments]);

        $this->db->beginTransaction();

        try {

            $insuranceFirmTable = new InsuranceFirmTable();
            $insuranceFirmTable->firm_name = $firmName;
            $insuranceFirmTable->comments = $comments;
            $insuranceFirmTable->save();

            $insurenceFirmBranchesTable = new InsuranceFirmBranchesTable();
            $insurenceFirmBranchesTable->insurance_code = $insuranceFirmTable->insurance_code;
            $insurenceFirmBranchesTable->branch_name = '_Generic_Branch_';
            $insurenceFirmBranchesTable->comments = 'This is the Generic Branch. Please select the correct Branch, or create a new Branch.';
            $insurenceFirmBranchesTable->save();

        } catch (\Throwable $e) {
            $this->logger->error('BrokerFirmBO->addBrokerFirms', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
        
        $this->db->commit();
        return true;

    }

    public function getBrokerFirmName($insuranceCode) {

        $firmName = '';

        try {

            $query = "select b.insurance_branch_code, i.* 
                    from insurance_firm_table i 
                    left join insurance_firm_branches_table b on i.insurance_code=b.insurance_code 
                    where b.insurance_branch_code =". $insuranceCode . "  
                    order by firm_name asc";
            $res = $this->db->query($query);

            if ($res) {
                $firmName = $res[0]->firm_name;
            }

        } catch (\Throwable $th) {

            return $firmName;

        }


        
        return $firmName;
    }

}
?>