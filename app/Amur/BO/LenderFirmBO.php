<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use App\Amur\Bean\IDB;
use App\Models\LenderFirmTable;
use App\Models\LenderFirmBranchesTable;
use App\Amur\BO\SalesforceBO;

class LenderFirmBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function index() {

        return LenderFirmTable::select('lender_firm_branches_table.lender_branch_code', 'lender_firm_table.*')
                ->join('lender_firm_branches_table', 'lender_firm_table.lender_code', '=', 'lender_firm_branches_table.lender_code')
                ->where('lender_firm_branches_table.branch_name', '=', '_Generic_Branch_')
                ->orderBy('lender_firm_table.firm_name', 'ASC')
                ->get();

    }

    public function addLenderFirms($firmName, $abbreviation, $comments) {

        $this->logger->info('LenderFirmBO->addLenderFirms',[$firmName, $abbreviation, $comments]);

        $this->db->beginTransaction();
        try {
            $lenderFirmTale = new LenderFirmTable();
            $lenderFirmTale->firm_name = $firmName ?? '';
            $lenderFirmTale->abbr = is_null($abbreviation) ? '' : $abbreviation;
            $lenderFirmTale->comments = is_null($comments) ? '' : $comments;
            $lenderFirmTale->save();

            $lenderFirmBranchesTable = new LenderFirmBranchesTable();
            $lenderFirmBranchesTable->lender_code = $lenderFirmTale->lender_code;
            $lenderFirmBranchesTable->branch_name = '_Generic_Branch_';
            $lenderFirmBranchesTable->comments = 'This is the Generic Branch. Please select the correct Branch, or create a new Branch.';
            $lenderFirmBranchesTable->save();

            $salesforceBO = new SalesforceBO($this->logger, $this->db);
            $salesforceBO->syncNewLendingFirm($lenderFirmTale->lender_code);

        } catch (\Throwable $e) {
            $this->logger->error('LenderFirmBO->addLenderFirms', [$e->getMessage(),$e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
        
        $this->db->commit();
        return true;
    }
}
