<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use Illuminate\Support\Facades\DB;
use App\Amur\Bean\ILogger;
use App\Amur\Utilities\Utils;
use App\Models\GeneralReportUser;
use App\Models\MicPipeline;
use App\Amur\Bean\SalesforceIntegration;
use App\Amur\BO\SalesforceBO;
use DateInterval;
use DateTime;


class StagePipelineBO {

    private $logger;
    private $db;
    private $originationPipeline = [];
    private $originationPipelineDetail = [];

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function index($startDate, $endDate) {
        $minRange = 80;
        $maxRange = 100;
        $seriesNames = ['Init Docs', 'Signing', 'Funding'];

        $chartData = array();
        foreach($seriesNames as $name) {
            $data = array();
            $tmp = clone $startDate;

            while($tmp <= $endDate) {
                // Highcharts expects milliseconds, so we append '000'
                $unixTimestamp = intval($tmp->getTimestamp() . '000');
                if($name == 'Init Docs') {
                    $data[] = [$unixTimestamp, rand($minRange, $maxRange)];
                } elseif($name == 'Signing') {
                    $data[] = [$unixTimestamp, rand($minRange - 20, $maxRange - 20)];
                } else {
                    $data[] = [$unixTimestamp, rand($minRange - 30, $maxRange - 30)];
                }

                $tmp->add(new DateInterval('P1D'));
            }

            $chartData[] = [
                'name' => $name,
                'data' => $data
            ];
        }

        //$chartData = $generateChartData($startDate, $endDate, 80, 100);
        
        return [
            'chartData' => $chartData,
        ];
    }

    public function loadData() {
        $reportBO = new ReportBO($this->logger, $this->db);

        // Get data from all three queries
        $initialDocsQuery = $reportBO->getInitialDocsQuery();
        $signingQuery = $reportBO->getSigningQuery();
        $fundingQuery = $reportBO->getFundingQuery();

        // Execute all queries and combine results
        $initialDocsRes = $this->db->select($initialDocsQuery);
        $signingRes = $this->db->select($signingQuery);
        $fundingRes = $this->db->select($fundingQuery);

        // Combine all results
        $allResults = array_merge($initialDocsRes, $signingRes, $fundingRes);

        $insertedCount = 0;
        $referenceDate = (new DateTime())->format('Y-m-d');

        // Delete existing records for today
        $this->db->delete('stage_pipeline', ['reference_date' => $referenceDate]);

        // Insert all records into stage_pipeline table
        foreach ($allResults as $key => $value) {
            $data = [
                'mortgage_id' => $value->mortgage_id ?? 0,
                'saved_quote_id' => $value->saved_quote_id ?? 0,
                'status_id' => $value->status_id ?? 0,
                'reference_date' => $referenceDate,
            ];
            
            if ($this->db->insert('stage_pipeline', $data)) {
                $insertedCount++;
            }
        }

        return [
            'initial_docs' => count($initialDocsRes),
            'signing' => count($signingRes),
            'funding' => count($fundingRes),
            'total_found' => count($allResults),
            'total_inserted' => $insertedCount
        ];
    }

    public function getCounts($month, $year) {
        // Default to today if no date provided
        if(is_null($month) || is_null($year)) {
            return false;
        }
   
        // Query to get counts grouped by status_id for the given date
        $query = "
            SELECT
                b.reference_date,
                b.status_id,
                COUNT(*) as count
            FROM stage_pipeline b
            JOIN saved_quote_table sq ON b.saved_quote_id = sq.saved_quote_id
            JOIN application_table c ON sq.application_id = c.application_id
            WHERE month(b.reference_date) = ? and year(b.reference_date) = ?
            AND c.company != 701
            GROUP BY b.reference_date, b.status_id
            ORDER BY b.reference_date, b.status_id
        ";
        $res = $this->db->select($query, [$month, $year]);

        $counts = [];
        foreach($res as $key => $value) {
            $referenceDate = $value->reference_date;

            if(!isset($counts[$referenceDate])) {
                $counts[$referenceDate] = [
                    'initial_docs_sent' => 0,
                    'initial_docs_received' => 0,
                    'signing' => 0,
                    'funding' => 0,
                    'total' => 0,
                    'date' => $referenceDate
                ];
            }

            $statusId = (int) $value->status_id;

            if($statusId == 8) {
                // Initial Docs Sent
                $counts[$referenceDate]['initial_docs_sent'] = $value->count;
            } elseif($statusId == 17) {
                // Initial Docs Received
                $counts[$referenceDate]['initial_docs_received'] = $value->count;
            } elseif(in_array($statusId, [10, 14])) {
                // Signing
                $counts[$referenceDate]['signing'] = $value->count;
            } elseif($statusId == 13) {
                // Funding
                $counts[$referenceDate]['funding'] = $value->count;
            }

            $counts[$referenceDate]['total'] += $value->count;
        }

        $startDate = new DateTime("$year-$month-01");
        $endDate = (clone $startDate)->modify('last day of this month');
        while($startDate <= $endDate) {
            $dateKey = $startDate->format('Y-m-d');
            if(!isset($counts[$dateKey])) {
                $counts[$dateKey] = [
                    'initial_docs_sent' => 0,
                    'initial_docs_received' => 0,
                    'signing' => 0,
                    'funding' => 0,
                    'total' => 0,
                    'date' => $dateKey,
                    'isToday' => ($dateKey == (new DateTime())->format('Y-m-d'))
                ];
            }
            $startDate->modify('+1 day');
        }

        //sort by date
        ksort($counts);

        return array_values($counts);
    }

    public function getCountsByDate($date = null) {
        // Default to today if no date provided
        if (!$date) {
            $date = (new DateTime())->format('Y-m-d');
        } else {
            // Ensure date is in Y-m-d format
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                // Already in Y-m-d format, use as-is
                $date = $date;
            } else {
                $parts = explode(' ', $date);
                $dateOnly = $parts[0];
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOnly)) {
                    $date = $dateOnly;
                } else {
                    // Fallback to DateTime parsing
                    $dateObj = new DateTime($date);
                    $date = $dateObj->format('Y-m-d');
                }
            }
        }

        // Query to get counts grouped by status_id for the given date
        $query = "
            SELECT 
                b.status_id,
                COUNT(*) as count
            FROM stage_pipeline b
            JOIN saved_quote_table sq ON b.saved_quote_id = sq.saved_quote_id
            JOIN application_table c ON sq.application_id = c.application_id
            WHERE b.reference_date = ?
            AND c.company != 701
            GROUP BY b.status_id
        ";

        $results = $this->db->select($query, [$date]);

        // Initialize counts
        $counts = [
            'initial_docs_sent' => 0,
            'initial_docs_received' => 0,
            'signing' => 0,
            'funding' => 0,
            'total' => 0,
            'date' => $date
        ];

        // Map status_id to stage names and sum counts
        foreach ($results as $row) {
            $statusId = (int)$row->status_id;
            $count = (int)$row->count;

            if ($statusId == 8) {
                // Initial Docs Sent
                $counts['initial_docs_sent'] += $count;
            } elseif ($statusId == 17) {
                // Initial Docs Received
                $counts['initial_docs_received'] += $count;
            } elseif (in_array($statusId, [10, 14])) {
                // Signing
                $counts['signing'] += $count;
            } elseif ($statusId == 13) {
                // Funding
                $counts['funding'] += $count;
            }

            $counts['total'] += $count;
        }

        return $counts;
    }

    /**
     * Get all records for a specific date
     */
    private function getRecordsByDate($date) {
        // Ensure date is in Y-m-d format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = $date;
        } else {
            $parts = explode(' ', $date);
            $dateOnly = $parts[0];
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOnly)) {
                $date = $dateOnly;
            } else {
                $dateObj = new DateTime($date);
                $date = $dateObj->format('Y-m-d');
            }
        }

        $query = "
            SELECT DISTINCT
                a.application_id,
                c.company,
                b.mortgage_id,
                b.saved_quote_id,
                b.status_id
            FROM saved_quote_table a
            JOIN stage_pipeline b ON a.saved_quote_id = b.saved_quote_id
            JOIN application_table c ON a.application_id = c.application_id
            WHERE b.reference_date = ?
            AND c.company != 701
        ";

        return $this->db->select($query, [$date]);
    }

    /**
     * Get the current status from application_table for each saved quote id.
     */
    private function getLatestStagesForSavedQuotes(array $savedQuoteIds) {
        if (empty($savedQuoteIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($savedQuoteIds), '?'));
        $query = "
            SELECT
                sq.saved_quote_id,
                app.status,
                st.salesforce_name as status_name
            FROM saved_quote_table sq
            JOIN application_table app ON sq.application_id = app.application_id
            LEFT JOIN status_table st ON app.status = st.id
            WHERE sq.saved_quote_id IN ($placeholders)
        ";

        $results = $this->db->select($query, $savedQuoteIds);

        $latestStages = [];
        foreach ($results as $row) {
            $latestStages[$row->saved_quote_id] = [
                'status_id' => $row->status ? (int)$row->status : null,
                'status_name' => $row->status_name ?? null,
                'updated_at' => null
            ];
        }

        return $latestStages;
    }

    /**
     * Get the stage name for a status_id
     */
    /*private function getStageName($statusId) {
        if ($statusId == 8) {
            return 'Initial Docs Sent';
        } elseif ($statusId == 17) {
            return 'Initial Docs Received';
        } elseif (in_array($statusId, [10, 14])) {
            return 'Signing';
        } elseif ($statusId == 13) {
            return 'Funding';
        }
        return 'Unknown';
    }*/

    public function compareDates($date1, $date2) {
        $counts1 = $this->getCountsByDate($date1);
        $counts2 = $this->getCountsByDate($date2);

        // Get detailed records for drill-down
        $records1 = $this->getRecordsByDate($date1);
        $records2 = $this->getRecordsByDate($date2);

        // Create lookup maps for date1 records
        // First, create a map by file key (mortgage_id|saved_quote_id) to track all statuses per file
        $date1Files = [];
        $date1RecordsByKey = [];
        foreach ($records1 as $record) {
            $fileKey = $record->mortgage_id . '|' . $record->saved_quote_id;
            $fullKey = $fileKey . '|' . $record->status_id;
            
            // Track which files exist (regardless of status)
            $date1Files[$fileKey] = true;
            
            // Track records by full key (including status_id) for comparison
            $date1RecordsByKey[$fullKey] = [
                'application_id' => $record->application_id ?? null,
                'mortgage_id' => $record->mortgage_id,
                'saved_quote_id' => $record->saved_quote_id,
                'status_id' => $record->status_id
            ];
        }

        // Create lookup maps for date2 records
        $date2Files = [];
        $date2RecordsByKey = [];
        foreach ($records2 as $record) {
            $fileKey = $record->mortgage_id . '|' . $record->saved_quote_id;
            $fullKey = $fileKey . '|' . $record->status_id;
            
            // Track which files exist (regardless of status)
            $date2Files[$fileKey] = true;
            
            // Track records by full key (including status_id) for comparison
            $date2RecordsByKey[$fullKey] = [
                'application_id' => $record->application_id ?? null,
                'mortgage_id' => $record->mortgage_id,
                'saved_quote_id' => $record->saved_quote_id,
                'status_id' => $record->status_id
            ];
        }

        // Find added records (in date2 but not in date1)
        // A record is "added" if the exact combination (file + status) exists in date2 but not in date1
        // This includes both new files and files that changed to this status
        $added = [];
        $addedKeys = []; // Track added keys to prevent duplicates
        foreach ($date2RecordsByKey as $fullKey => $record) {
            // Add if this exact record (file + status) doesn't exist in date1
            if (!isset($date1RecordsByKey[$fullKey]) && !isset($addedKeys[$fullKey])) {
                $added[] = $record;
                $addedKeys[$fullKey] = true;
            }
        }

        // Find removed records (in date1 but not in date2)
        // A record is "removed" if the exact combination (file + status) exists in date1 but not in date2
        // This includes files that were removed entirely and files that changed from this status
        $removed = [];
        $removedKeys = []; // Track removed keys to prevent duplicates
        foreach ($date1RecordsByKey as $fullKey => $record) {
            // Remove if this exact record (file + status) doesn't exist in date2
            // AND it's not already in the added list (to prevent same file+status appearing in both)
            if (!isset($date2RecordsByKey[$fullKey]) && !isset($removedKeys[$fullKey]) && !isset($addedKeys[$fullKey])) {
                $removed[] = $record;
                $removedKeys[$fullKey] = true;
            }
        }

        // Attach the most recent stage info for removed records so the UI can show where they moved
        $savedQuoteIds = array_values(array_unique(array_filter(array_column($removed, 'saved_quote_id'))));
        $latestStages = $this->getLatestStagesForSavedQuotes($savedQuoteIds);
        
        // Collect application_ids that have status_id 18 for batch close reason lookup
        $applicationIdsForCloseReason = [];
        foreach ($removed as $record) {
            $savedQuoteId = $record['saved_quote_id'];
            if ($savedQuoteId && isset($latestStages[$savedQuoteId])) {
                if ($latestStages[$savedQuoteId]['status_id'] == 18 && !empty($record['application_id'])) {
                    $applicationIdsForCloseReason[] = $record['application_id'];
                }
            }
        }
        
        // Get all close reasons in one query for applications with status_id 18
        $closeReasons = [];
        if (!empty($applicationIdsForCloseReason)) {
            $applicationIdsForCloseReason = array_unique($applicationIdsForCloseReason);
            $placeholders = implode(',', array_fill(0, count($applicationIdsForCloseReason), '?'));
            $closeReasonQuery = "
                SELECT a.application_id, a.status_id, c.name as close_reason
                FROM sales_journey a
                JOIN application_table b ON a.application_id = b.application_id
                JOIN turndownreason_table c ON a.close_reason_id = c.id
                WHERE b.application_id IN ($placeholders)
            ";
            $closeReasonResults = $this->db->select($closeReasonQuery, $applicationIdsForCloseReason);
            foreach ($closeReasonResults as $row) {
                $closeReasons[$row->application_id] = $row->close_reason;
            }
        }
        
        foreach ($removed as &$record) {
            $savedQuoteId = $record['saved_quote_id'];
            if ($savedQuoteId && isset($latestStages[$savedQuoteId])) {
                $record['current_stage'] = $latestStages[$savedQuoteId]['status_name'];
                $record['current_stage_updated_at'] = $latestStages[$savedQuoteId]['updated_at'];
                $record['current_status_id'] = $latestStages[$savedQuoteId]['status_id'];
                
                // Get close reason if status is 18 (closed lost)
                if ($latestStages[$savedQuoteId]['status_id'] == 18 && !empty($record['application_id'])) {
                    $record['close_reason'] = $closeReasons[$record['application_id']] ?? null;
                } else {
                    $record['close_reason'] = null;
                }
            } else {
                $record['current_stage'] = null;
                $record['current_stage_updated_at'] = null;
                $record['current_status_id'] = null;
                $record['close_reason'] = null;
            }
        }
        unset($record);

        // Group added and removed by stage for counts
        $addedByStage = [
            'initial_docs_sent' => 0,
            'initial_docs_received' => 0,
            'signing' => 0,
            'funding' => 0
        ];

        $removedByStage = [
            'initial_docs_sent' => 0,
            'initial_docs_received' => 0,
            'signing' => 0,
            'funding' => 0
        ];

        foreach ($added as $record) {
            $statusId = (int)$record['status_id'];
            if ($statusId == 8) {
                $addedByStage['initial_docs_sent']++;
            } elseif ($statusId == 17) {
                $addedByStage['initial_docs_received']++;
            } elseif (in_array($statusId, [10, 14])) {
                $addedByStage['signing']++;
            } elseif ($statusId == 13) {
                $addedByStage['funding']++;
            }
        }

        foreach ($removed as $record) {
            $statusId = (int)$record['status_id'];
            if ($statusId == 8) {
                $removedByStage['initial_docs_sent']++;
            } elseif ($statusId == 17) {
                $removedByStage['initial_docs_received']++;
            } elseif (in_array($statusId, [10, 14])) {
                $removedByStage['signing']++;
            } elseif ($statusId == 13) {
                $removedByStage['funding']++;
            }
        }

        return [
            'date1' => $counts1,
            'date2' => $counts2,
            'differences' => [
                'initial_docs_sent' => $counts2['initial_docs_sent'] - $counts1['initial_docs_sent'],
                'initial_docs_received' => $counts2['initial_docs_received'] - $counts1['initial_docs_received'],
                'signing' => $counts2['signing'] - $counts1['signing'],
                'funding' => $counts2['funding'] - $counts1['funding'],
                'total' => $counts2['total'] - $counts1['total']
            ],
            'drill_down' => [
                'added' => [
                    'records' => $added,
                    'count' => count($added),
                    'by_stage' => $addedByStage
                ],
                'removed' => [
                    'records' => $removed,
                    'count' => count($removed),
                    'by_stage' => $removedByStage
                ]
            ]
        ];
    }

    public function getTurndownReason($application_id) {
        $sfi = new SalesforceIntegration($this->db, $this->logger);
        $sfi->getByObjectId('application_table', $application_id);
        $opportunityId = $sfi->getSalesforceId();

        if (!$opportunityId) {
            $this->logger->info('getTurndownReason - No Salesforce ID found', [$application_id]);
            return null;
        }

        $query = "SELECT Loss_Reason__c FROM Opportunity WHERE Id = '$opportunityId'";

        $salesforceBO = new SalesforceBO($this->logger, $this->db);

        $lossReason = null;

        $response = $salesforceBO->query($query);

        if ($response && !empty($response->records) && !empty($response->records[0]->Loss_Reason__c)) {
            $lossReason = $response->records[0]->Loss_Reason__c;
        }

        $this->logger->info('getTurndownReason', [$application_id, $lossReason]);
        
        return $lossReason;
    }
}
