<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use App\Models\GroupMembersTable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AccessRightBO {

    private $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public static function check($role) {
        $userId = Auth::user()->user_id;
        $groups = static::getGroup($role);

        Log::info('AccessRightBO::check', ['userId' => $userId, 'groups' => $groups, 'role' => $role]);

        $gm = GroupMembersTable::query()
            ->where('user_id', $userId)
            ->where('deleted', 0)
            ->get();

        foreach ($gm as $key => $value) {
            if (in_array($value->group_id, $groups) || $value->group_id == 8) {
                return true;
            }
        }

        return false;
    }

    public static function getGroup($role) {
        switch ($role) {
            case 'pap':
                return [9, 19, 21];
            case 'cms':
                return [31, 32, 34, 36, 52];
            case 'cmsagent':
                return [33];
            case 'cmsshare':
                return [52, 36, 34, 33, 32, 31];
            default:
                return [];
        }
    }
}
