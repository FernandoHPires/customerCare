<?php

namespace App\Amur\BO;

use Illuminate\Support\Facades\Auth;
use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\NotesTable;
use App\Models\TasksTable;
use DateTime;

class NotesBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function new(
        $applicationId,
        $mortgageId,
        $categoryId,
        $noteText,
        $followerUp = null,
        $followedUp = null,
        $userId = null
    ) {

        $userId = is_null($userId) ? Auth::user()->user_id : $userId;

        $note = new NotesTable();
        $note->application_id = $applicationId;
        $note->mortgage_id = $mortgageId;
        $note->author_id = $userId;
        $note->category_id = $categoryId;
        $note->note_date_time = new DateTime();
        $note->followup_date = new DateTime();
        $note->follower_up = is_null($followerUp) ? $userId : $followerUp;
        $note->followed_up = is_null($followedUp) ? 'yes' : $followedUp;
        $note->note_text = $noteText;
        $note->last_updated = new DateTime();
        $note->last_updated_by = $userId;
        $note->update_times = 0;
        $note->turndown_id = 1;
        $note->turndown_date = '0000-00-00';
        $note->note_id_assigned = 0;
        $note->save();
    }

    public function getFollowerUpByMortgageId($mortgageId) {
        $query = "select d.province, b.company
                    from mortgage_table a
                    join application_table b on a.application_id = b.application_id
               left join mortgage_properties_table c on a.mortgage_id = c.mortgage_id
               left join property_table d on c.property_id = d.property_id
                   where a.mortgage_id = $mortgageId
                    order by d.property_id";
        $res = $this->db->select($query);

        if(count($res) > 0) {
            $province = $res[0]->province;
            $companyId = $res[0]->company;

            $tasksTable = TasksTable::query()
            ->where('tc_type_id', 29)
            ->where('tc_province_id', $province)
            ->whereIn('tc_company_id', [1,$companyId])
            ->orderBy('tc_company_id', 'desc')
            ->first();

            if($tasksTable) {
                return $tasksTable->tc_operator_id;
            }
        }

        return null;
    }

}
