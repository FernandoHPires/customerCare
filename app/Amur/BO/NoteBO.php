<?php

namespace App\Amur\BO;

use App\Amur\Bean\IDB;
use App\Amur\Bean\ILogger;
use App\Models\TurndownReasonTable;
use App\Models\NoteCategoriesTable;
use App\Models\UsersTable;
use App\Models\NotesTable;
use DateTime;
use Illuminate\Support\Facades\Auth;
use App\Models\SalesforceIntegration;

class NoteBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function show($noteId, $opportunityId) {

        $applicationId = 0;

        if(substr($opportunityId,0,3) == '006') {

            $sfi = SalesforceIntegration::query()
            ->where('salesforce_id', $opportunityId)
            ->where('salesforce_object', 'Opportunity')
            ->first();

            if ($sfi) {
                $applicationId = $sfi->object_id;
            }
            
        } else {
            $applicationId = $opportunityId;
        }

        $followerUp = 0;
        $noteData = array();
        
        if($noteId > 0) {
            $notesTable = NotesTable::find($noteId);
            if($notesTable) {
                $noteText = $notesTable->note_text;
                //$noteText = preg_replace('/(\\r\\n){3,}/', "\r\n\r\n", $noteText);
                $noteText = preg_replace('/^(\\r\\n){1,}/', '', $noteText);
                $noteText = preg_replace('/(\\r\\n)/', "<br>", $noteText);
                $noteText = preg_replace('/(\\n\\r)/', "<br>", $noteText);
                $noteText = preg_replace('/(\\n)/', "<br>", $noteText);
                $noteText = preg_replace('/(\\r)/', "<br>", $noteText);
    
                $date = '';
                if(
                    isset($notesTable->followup_date) &&
                    !empty($notesTable->followup_date) &&
                    $notesTable->followup_date != '0000-00-00' &&
                    $notesTable->followup_date != '0000-00-00 00:00:00'
                ) {
                    $date = (new DateTime($notesTable->followup_date))->format('Y-m-d');
                }
    
                $followerUp = $notesTable->follower_up;
    
                $noteData = [
                    'noteId' => $notesTable->note_id,
                    'followUpDate' => $date,
                    'noteText' => $noteText,
                    'followerUp' => $followerUp,
                    'followedUp' => $notesTable->followed_up,
                    'categoryId' => $notesTable->category_id,
                    'turnDownId' => $notesTable->turndown_id
                ];
    
                $applicationId = $notesTable->application_id;
            }
        } else {
            $noteData = [
                'noteId' => 0,
            ];
        }
        

        $noteDatails = array();
        $turnDownReasons = array();
        $categoriesData = array();
        $usersData = array();
        
        $turnDownReasons = $this->getTurnDownReasons();
        $categoriesData  = $this->getNoteCategories();

        $usersData = $this->getUsers($followerUp);

        $noteDatails = [
            'turnDownReasons' => $turnDownReasons,
            'categoriesData' => $categoriesData,
            'usersData' => $usersData,
            'applicationId' => $applicationId,
            'noteData' => $noteData
        ];

        return $noteDatails;
    }    

    public function store($applicationId, $noteData) {

        $this->logger->info('NoteBO->storeNote', [$applicationId, $noteData]);

        $userId = Auth::user()->user_id;

        if(isset($noteData['noteId']) && !empty($noteData['noteId'])) {
            $notesTable = NotesTable::find($noteData['noteId']);
        } else {
            $notesTable = null;
        }

        if(!$notesTable) {
            $notesTable = new NotesTable();
            $notesTable->application_id = $applicationId;
            $notesTable->author_id = $userId;
            $notesTable->category_id = $noteData['categoryId'] ?? 0;
            $notesTable->note_date_time = (new DateTime())->format('Y-m-d');
            $notesTable->turndown_id = 1;
        }

        $noteText = '';
        if(isset($noteData['noteText']) && !empty($noteData['noteText'])) {
            $noteText = preg_replace('/<div><br><\/div>/', "\n", $noteData['noteText']);
            $noteText = preg_replace('/<br>/', "\n", $noteText);
            $noteText = preg_replace('/<div>/', "\n", $noteText);
            $noteText = preg_replace('/<\/div>/', "", $noteText);
        }

        $notesTable->followup_date = $noteData['followUpDate'] ?? '';
        $notesTable->followed_up = $noteData['followedUp'] ?? 'no';
        $notesTable->follower_up = isset($noteData['followerUp']) && !empty($noteData['followerUp']) ? $noteData['followerUp'] : $userId;
        $notesTable->note_text = $noteText ?? '';
        $notesTable->category_id = $noteData['categoryId'] ?? 0;
        $notesTable->last_updated = new DateTime();
        $notesTable->last_updated_by = $userId;
        $notesTable->save();

        $apiBO = new ApiBO($this->logger);
        $apiBO->notes($notesTable->note_id);

        return true;
    }

    public function destroy($id) {
        
        $note = NotesTable::find($id);
        if($note) {
            $note->delete();
            $this->logger->info('NoteBO->destroyNote', [$id]);
            $apiBO = new ApiBO($this->logger);
            $apiBO->destroyNotes($note->note_id);
            
            return true;
        }

        return false;
    }

    public function getTurnDownReasons() {

        $turndownReasonTable = TurndownReasonTable::query()
        ->get();

        return $turndownReasonTable;
    }

    public function getNoteCategories() {

        $noteCategoriesTable = NoteCategoriesTable::query()
        ->orderBy('category_name')
        ->get();

        $data = array();
        foreach($noteCategoriesTable as $key => $value) {
            $data[] = [
                'id' => $value->category_id,
                'name' => $value->category_name
            ];
        }

        return $data;
    }

    public function getUsers($followerUp) {

        $usersObj = UsersTable::query()
        ->where('inuse', 'yes')
        ->orWhere('user_id', $followerUp)
        ->orderBy('user_fname')
        ->get();

        $users = array();
        foreach($usersObj as $key => $value) {
            if($value->user_fname != null || $value->user_lname != null) {                
                $users[] = [
                    'id' => $value->user_id,
                    'username' => $value->user_name,
                    'fullName' => $value->user_fname . ' ' . $value->user_lname,
                    'firstName' => $value->user_fname,
                    'lastName' => $value->user_lname,
                    'email' => $value->user_email
                ];
            }
        }

        return $users;
    }
}