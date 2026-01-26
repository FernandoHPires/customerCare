<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Amur\Utilities\Utils;
use Carbon\Carbon;
use App\Amur\BO\ReportBO;
use DateTime;

class MicForecastBO {

    private $logger;
    private $db;
    private array $micForecast = [];

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getMicForecast($fundingBuffer, $signingBuffer, $initialDocsBuffer) {

        $this->logger->info('MicForecastBO->getMicForecast', [$fundingBuffer, $signingBuffer, $initialDocsBuffer]);

        $reportBO = new ReportBO($this->logger, $this->db);

        $result = $reportBO->getFunded('Mic Forcast','returnResult');
        $this->ProcessData($result, 'Funded', $fundingBuffer, $signingBuffer, $initialDocsBuffer);

        $result = $reportBO->getFunding('returnResult', new DateTime());
        $this->ProcessData($result, 'Funding', $fundingBuffer, $signingBuffer, $initialDocsBuffer);

        $result = $reportBO->getSigning('returnResult', new DateTime());
        $this->ProcessData($result, 'Signing', $fundingBuffer, $signingBuffer, $initialDocsBuffer);

        $result = $reportBO->getInitialDocs('returnResult');
        $this->ProcessData($result, 'Initial Docs', $fundingBuffer, $signingBuffer, $initialDocsBuffer);

        $totalData = $this->getTotalMicForecast($fundingBuffer, $signingBuffer, $initialDocsBuffer);

        $micForecastAux = $this->removeReference();

        $data = [
            'micForecast' => $micForecastAux,
            'totalMicForecast' => $totalData['totalMicForecast'],
            'totalMicForecastNextMonth' => $totalData['totalMicForecastNextMonth'],
        ];

        return $data;
    }

    public function processData($result, $statusOrder, $fundingBuffer, $signingBuffer, $initialDocsBuffer) {

        $this->logger->info('MicForecastBO->processData', [$statusOrder, $fundingBuffer, $signingBuffer, $initialDocsBuffer]);

        foreach ($result as $key => $value) {

            $reference = '';

            $fundingDate = $value->funding_date;

            if ($statusOrder == 'Funded') {

                $reference = 'current';
            } elseif ($statusOrder == 'Funding') {

                $reference = $this->checkRange($fundingBuffer, $fundingDate);
            } elseif ($statusOrder == 'Signing') {

                $reference = $this->checkRange($signingBuffer, $fundingDate);
            } elseif ($statusOrder == 'Initial Docs') {

                $reference = $this->checkRange($initialDocsBuffer, $fundingDate);
            }

            if ($reference == '') {
                $this->logger->info('MicForecastBO->processData No Reference', [$statusOrder, $fundingDate]);
                continue;
            }

            if (($value->investor_id == 31 || $value->investor_id == 248 || $value->investor_id == 100 || $value->investor_id == 1971)
                && ($value->origination_company_id == 1 || $value->origination_company_id == 701)) {

                $exists = false;

                if ($value->investor_id == 1971 && ($statusOrder == 'Signing' || $statusOrder == 'Initial Docs')) {

                    if (!is_null($value->ap_inv_co)) {

                        $exists = false;
                        $investorId  = Utils::convertCompanyToInvestor($value->ap_inv_co);
                        $grossAmount = $value->ap_amount;
                        $ltv         = 0;
                        $yield       = $value->ap_yield;

                        foreach ($this->micForecast as &$doc) {
                            if ($doc['reference'] == $reference && $doc['statusOrder'] == $statusOrder && $doc['companyId'] == $value->origination_company_id && $doc['investorId'] == $investorId) {
                                $doc['count'] += 1;
                                $doc['grossAmount'] += $grossAmount;
                                $doc['ltv'] += $grossAmount * $ltv;
                                $doc['yield'] += $grossAmount * $yield;
                                $exists = true;
                                break;
                            }
                        }

                        if (!$exists) {
                            $this->micForecast[] = [
                                'reference'   => $reference,
                                'statusOrder' => $statusOrder,
                                'companyId'   => $value->origination_company_id,
                                'investorId'  => $investorId,
                                'count'       => 1,
                                'grossAmount' => $grossAmount,
                                'ltv'         => $grossAmount * $ltv,
                                'yield'       => $grossAmount * $yield
                            ];
                        }
                    }

                    if (!is_null($value->bp_inv_co)) {

                        $exists = false;
                        $investorId  = Utils::convertCompanyToInvestor($value->bp_inv_co); //, 'amount' => $value->bp_amount, 'yield' => $value->bp_yield];
                        $grossAmount = $value->bp_amount;
                        $ltv         = 0;
                        $yield       = $value->bp_yield;

                        foreach ($this->micForecast as &$doc) {
                            if ($doc['reference'] == $reference && $doc['statusOrder'] == $statusOrder && $doc['companyId'] == $value->origination_company_id && $doc['investorId'] == $investorId) {
                                $doc['count'] += 1;
                                $doc['grossAmount'] += $grossAmount;
                                $doc['ltv'] += $grossAmount * $ltv;
                                $doc['yield'] += $grossAmount * $yield;
                                $exists = true;
                                break;
                            }
                        }

                        if (!$exists) {
                            $this->micForecast[] = [
                                'reference'   => $reference,
                                'statusOrder' => $statusOrder,
                                'companyId'   => $value->origination_company_id,
                                'investorId'  => $investorId,
                                'count'       => 1,
                                'grossAmount' => $grossAmount,
                                'ltv'         => $grossAmount * $ltv,
                                'yield'       => $grossAmount * $yield
                            ];
                        }
                    }

                    if (!is_null($value->cp_inv_co)) {

                        $exists = false;
                        $investorId  = Utils::convertCompanyToInvestor($value->cp_inv_co); //, 'amount' => $value->cp_amount, 'yield' => $value->cp_yield];
                        $grossAmount = $value->cp_amount;
                        $ltv         = 0;
                        $yield       = $value->cp_yield;

                        foreach ($this->micForecast as &$doc) {
                            if ($doc['reference'] == $reference && $doc['statusOrder'] == $statusOrder && $doc['companyId'] == $value->origination_company_id && $doc['investorId'] == $investorId) {
                                $doc['count'] += 1;
                                $doc['grossAmount'] += $grossAmount;
                                $doc['ltv'] += $grossAmount * $ltv;
                                $doc['yield'] += $grossAmount * $yield;
                                $exists = true;
                                break;
                            }
                        }

                        if (!$exists) {
                            $this->micForecast[] = [
                                'reference'   => $reference,
                                'statusOrder' => $statusOrder,
                                'companyId'   => $value->origination_company_id,
                                'investorId'  => $investorId,
                                'count'       => 1,
                                'grossAmount' => $grossAmount,
                                'ltv'         => $grossAmount * $ltv,
                                'yield'       => $grossAmount * $yield
                            ];
                        }
                    }
                } else {

                    $investorId  = $value->investor_id;
                    $grossAmount = $value->gross_amount ?? $value->gross_amt;
                    $ltv         = $value->ltv;
                    $yield       = $value->yield;

                    foreach ($this->micForecast as &$doc) {
                        if ($doc['reference'] == $reference && $doc['statusOrder'] == $statusOrder && $doc['companyId'] == $value->origination_company_id && $doc['investorId'] == $investorId) {
                            $doc['count'] += 1;
                            $doc['grossAmount'] += $grossAmount;
                            $doc['ltv'] += $grossAmount * $ltv;
                            $doc['yield'] += $grossAmount * $yield;
                            $exists = true;
                            break;
                        }
                    }

                    if (!$exists) {
                        $this->micForecast[] = [
                            'reference'   => $reference,
                            'statusOrder' => $statusOrder,
                            'companyId'   => $value->origination_company_id,
                            'investorId'  => $investorId,
                            'count'       => 1,
                            'grossAmount' => $grossAmount,
                            'ltv'         => $grossAmount * $ltv,
                            'yield'       => $grossAmount * $yield
                        ];
                    }
                }
            }
        }
    }

    public function getTotalMicForecast($fundingBuffer, $signingBuffer, $initialDocsBuffer) {

        $this->logger->info('MicForecastBO->getTotalMicForecast');

        $totalMicForecast = array();
        $totalMicForecastNextMonth = $totalMicForecast = array();

        foreach ($this->micForecast as $key => $value) {

            $exists = false;

            if ($value['reference'] == 'current') {

                foreach ($totalMicForecast as &$totalMic) {
                    if ($totalMic['reference'] == $value['reference'] && $totalMic['statusOrder'] == $value['statusOrder'] && $totalMic['investorId'] == $value['investorId']) {
                        $totalMic['count']       += $value['count'];
                        $totalMic['grossAmount'] += $value['grossAmount'];
                        $totalMic['ltv']         += $value['ltv'];
                        $totalMic['yield']       += $value['yield'];

                        $exists = true;
                        break;
                    }
                }
            } elseif ($value['reference'] == 'next') {

                foreach ($totalMicForecastNextMonth as &$totalMic) {
                    if ($totalMic['reference'] == $value['reference'] && $totalMic['statusOrder'] == $value['statusOrder'] && $totalMic['investorId'] == $value['investorId']) {
                        $totalMic['count']       += $value['count'];
                        $totalMic['grossAmount'] += $value['grossAmount'];
                        $totalMic['ltv']         += $value['ltv'];
                        $totalMic['yield']       += $value['yield'];

                        $exists = true;
                        break;
                    }
                }
            }


            if (!$exists) {
                if ($value['reference'] == 'current') {

                    $totalMicForecast[] = [
                        'reference'   => $value['reference'],
                        'statusOrder' => $value['statusOrder'],
                        'companyId'   => 88888888,
                        'investorId'  => $value['investorId'],
                        'count'       => $value['count'],
                        'grossAmount' => $value['grossAmount'],
                        'ltv'         => $value['ltv'],
                        'yield'       => $value['yield']
                    ];
                } elseif ($value['reference'] == 'next') {

                    $totalMicForecastNextMonth[] = [
                        'reference'   => $value['reference'],
                        'statusOrder' => $value['statusOrder'],
                        'companyId'   => 99999999,
                        'investorId'  => $value['investorId'],
                        'count'       => $value['count'],
                        'grossAmount' => $value['grossAmount'],
                        'ltv'         => $value['ltv'],
                        'yield'       => $value['yield']
                    ];
                }
            }
        }

        foreach ($totalMicForecast as &$doc) {
            $doc['ltv']   = round($doc['ltv']   / $doc['grossAmount'], 2);
            $doc['yield'] = round($doc['yield'] / $doc['grossAmount'], 2);
        }

        foreach ($totalMicForecastNextMonth as &$doc) {
            $doc['ltv']   = round($doc['ltv']   / $doc['grossAmount'], 2);
            $doc['yield'] = round($doc['yield'] / $doc['grossAmount'], 2);
        }

        return [
            'totalMicForecast' => $totalMicForecast,
            'totalMicForecastNextMonth' => $totalMicForecastNextMonth
        ];
    }

    public function checkRange($buffer, $fundingDate) {

        $now = Carbon::now();
        $reference = '';

        if ($fundingDate == null || $fundingDate == '' || $fundingDate == '0000-00-00') {

            $reference = '';
        } else {

            $fundingDate = Carbon::parse($fundingDate);
            $newFundingDate = $fundingDate->addDays($buffer);

            if ($newFundingDate->isSameMonth($now)) {

                $reference = 'current';
            } elseif ($newFundingDate->isSameMonth($now->addMonth())) {

                $reference = 'next';
            }
        }

        return $reference;
    }

    public function removeReference() {

        $micForecastAux = array();

        foreach ($this->micForecast as $key => $value) {

            if ($value['reference'] == 'current') {

                $exists = false;

                foreach ($micForecastAux as &$doc) {
                    if ($doc['statusOrder'] == $value['statusOrder'] && $doc['companyId'] == $value['companyId'] && $doc['investorId'] == $value['investorId']) {

                        $doc['count'] += $value['count'];
                        $doc['grossAmount'] += $value['grossAmount'];
                        $doc['ltv'] += $value['ltv'];
                        $doc['yield'] += $value['yield'];

                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    $micForecastAux[] = [
                        'statusOrder' => $value['statusOrder'],
                        'companyId'   => $value['companyId'],
                        'investorId'  => $value['investorId'],
                        'count'       => $value['count'],
                        'grossAmount' => $value['grossAmount'],
                        'ltv'         => $value['ltv'],
                        'yield'       => $value['yield']
                    ];
                }
            }
        }

        foreach ($micForecastAux as &$doc) {
            $doc['ltv']   = round($doc['ltv']   / $doc['grossAmount'], 2);
            $doc['yield'] = round($doc['yield'] / $doc['grossAmount'], 2);
        }

        return $micForecastAux;
    }
}
