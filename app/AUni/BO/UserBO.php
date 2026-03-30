<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\Models\GroupMembersTable;
use App\Models\UsersTable;
use App\Models\GroupScreen;
use App\Models\Menu;
use Illuminate\Support\Facades\Auth;

class UserBO {

    private $logger;
    private $db;
    private $menuList = [];

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function index() {

        $usersObj = UsersTable::query()
            ->where('inuse', 'yes')
            ->orderBy('user_fname')
            ->get();

        $users = array();
        if ($usersObj) {
            foreach ($usersObj as $key => $value) {
                $users[] = [
                    'id' => $value->user_id,
                    'username' => $value->user_name,
                    'fullName' => $value->user_fname . ' ' . $value->user_lname,
                    'firstName' => $value->user_fname,
                    'lastName' => $value->user_lname,
                    'email' => $value->user_email,
                    'userDepartment' => $value->user_dept,
                    'trackerView' => $value->doctracker_view,
                    'isAdmin' => $value->admin,
                    'companyId' => $value->default_company_id
                ];
            }
        }

        return $users;
    }

    public function show($id) {

        $userObj = UsersTable::query()
        ->where('user_id', $id)
        ->first();

        $user = array();

        if ($userObj) {
            $user = [
                'id' => $userObj->user_id,
                'username' => $userObj->user_name,
                'fullName' => $userObj->user_full_name,
                'firstName' => $userObj->user_full_name,
                'lastName' => $userObj->user_full_name,
                'email' => $userObj->user_email,
                'trackerManager' => '',
                'trackerView' => '',
                'isAdmin' => '',
                'companyId' => $userObj->default_company_id
            ];
        }

        return $user;
    }

    public function getBdmData() {
        $usersObj = UsersTable::query()
            ->where('inuse', 'yes')
            ->where('is_bdm', 'yes')
            ->orderBy('user_name')
            ->get();

        $users = array(
            [
                "id" => "",
                "name" => ""
            ],
            [
                "id" => 4912,
                "name" => "CollinJ"
            ]
        );
        if ($usersObj) {
            foreach ($usersObj as $key => $value) {
                $users[] = [
                    'id' => $value->user_id,
                    'name' => $value->user_name,
                ];
            }
        }

        return $users;
    }

    public function getAgentsData() {

        $usersObj = UsersTable::query()
            ->where('inuse', 'yes')
            ->where('agent', 'yes')
            ->orderBy('user_fname')
            ->get();

        if ($usersObj) {
            foreach ($usersObj as $key => $value) {
                $users[] = [
                    'id' => $value->user_id,
                    'name' => $value->user_name,
                    'fullName' => $value->user_fname . ' ' . $value->user_lname,
                ];
            }
        }

        return $users;
    }

    public function store($id, $username, $firstName, $lastName, $email) {
        $user = UsersTable::find($id);

        if ($user) {
            $user->user_name = $username;
            $user->user_fname = $firstName;
            $user->user_lname = $lastName;
            $user->user_email = $email;
            $user->save();
        } else {
            $user = new UsersTable();
            $user->crm_user_id = ' ';
            $user->user_password = ' ';
            $user->user_phone = ' ';
            $user->user_name = $username;
            $user->user_fname = $firstName;
            $user->user_lname = $lastName;
            $user->user_email = $email;
            $user->save();
        }

        return true;
    }

    public function destroy($id) {
        $user = UsersTable::find($id);

        if ($user) {
            $user->inuse = 'no';
            $user->save();
            return true;
        } else {
            return false;
        }
    }

    public function getCurrentUserGroups($id) {
        $userGroups = UsersTable::select(
                'users_table.user_fname',
                'users_table.user_lname',
                'users_table.user_name',
                'users_table.user_email',
                'users_table.user_id',
                'users_table.default_company_id',
                'group_members_table.group_id'
            )
            ->leftJoin('group_members_table', 'group_members_table.user_id', '=', 'users_table.user_id')
            ->where('users_table.user_id', $id)
            ->get(); 

        if ($userGroups->isEmpty()) {
            return false;
        }

        $first = $userGroups->first();

        $user = [
            'id' => $first->user_id,
            'userName' => $first->user_name,
            'fullName' => $first->user_fname . ' ' . $first->user_lname,
            'companyId' => $first->default_company_id,
            'email' => $first->user_email,
            'groupIds' => $userGroups->pluck('group_id')->toArray(),
        ];

        return $user;
    }

    public function getUsersByGroups($groupIds) {
        if (!is_array($groupIds)) {
            $groupIds = [$groupIds];
        }

        $usersObj = UsersTable::select(
                'users_table.user_fname',
                'users_table.user_lname',
                'users_table.user_name',
                'users_table.user_email',
                'users_table.user_id',
                'users_table.default_company_id',
                'group_members_table.group_id'
            )
            ->leftJoin('group_members_table', 'group_members_table.user_id', '=', 'users_table.user_id')
            ->whereIn('group_members_table.group_id', $groupIds)
            ->get();

        $users = [];

        if ($usersObj) {
            foreach ($usersObj as $value) {
                $users[] = [
                    'id' => $value->user_id,
                    'userName' => $value->user_name,
                    'fullName' => $value->user_fname . ' ' . $value->user_lname,
                    'companyId' => $value->default_company_id,
                    'email' => $value->user_email,
                    'groupId' => $value->group_id,
                ];
            }
        }

        return $users;
    }

    public function getMenus($userId) {

        $this->logger->info('UserBO->getMenus',[$userId]);

        $fullMenu = $this->getMenu();

        $gm = GroupMembersTable::query()
        ->where('user_id', $userId)
        ->get();

        foreach ($gm as $key => $value) {
            $this->getGroupScreen($value->group_id);
        }

        $userMenuIds = $this->menuList;

        $filteredMenu = array_filter($fullMenu, function ($item) use ($userMenuIds) {
            return in_array($item['id'], $userMenuIds);
        });

        $submenus = array();
        $menus    = array();
        $menus[]  = [
            'path' => '/',
            'name' => 'Home',
            'icon' => 'bi-house-door'
        ];

        foreach ($filteredMenu as $key => $value) {

            if ($value['parentId'] == null) {

                $submenus     = array();
                $lastSubGroup = null;

                foreach ($filteredMenu as $keyParent => $valueParent) {
                    if ($value['id'] == $valueParent['parentId']) {
                        
                        if ($valueParent['subGroup'] != $lastSubGroup && $valueParent['subGroup'] != null && $lastSubGroup != null) {
                            $submenus[] = ['type' => 'divider'];
                        }

                        $submenus[] = [
                            'type' => 'page',
                            'path' => $valueParent['url'],
                            'name' => $valueParent['label'],
                            'icon' => $valueParent['icon']
                        ];

                        $lastSubGroup = $valueParent['subGroup'];
                    }
                }

                if ($value['url'] == '') {
                    $url = null;
                }else {
                    $url = $value['url'];
                }

                if (count($submenus) > 0) {
                    $menus[] = [
                        'path'     => $url,
                        'name'     => $value['label'],
                        'icon'     => $value['icon'],
                        'submenus' => $submenus
                    ];
                }else {
                    $menus[] = [
                        'path'     => $url,
                        'name'     => $value['label'],
                        'icon'     => $value['icon']
                    ];
                }

            }
        }

        return $menus;
    }

    public function getGroupScreen($groupId) {

        $groupScreen = GroupScreen::query()
        ->where('group_id', $groupId)
        ->get();

        foreach ($groupScreen as $key => $value) {

            $menu = Menu::query()
            ->where('id', $value->menu_id)
            ->whereNotNull('parent_id')
            ->first();

            if ($menu) {
                if (!in_array($menu->parent_id, $this->menuList)) {
                    $this->menuList[] = $menu->parent_id;
                }
            }


            $this->menuList[] = $value->menu_id;
        }

    }

    public function getMenu() {

        $fullMenu = array();
        
        $menu = Menu::query()
        ->where('parent_id', null)
        ->orderBy('sequence')
        ->get();

        foreach ($menu as $key => $value) {

            $fullMenu[] = [
                'id'        => $value->id,
                'parentId'  => $value->parent_id,
                'label'     => $value->label,
                'url'       => $value->url,
                'subGroup'  => $value->sub_group,
                'icon'      => $value->icon,
                'sequence'  => $value->sequence
            ];

            $subMenu = Menu::query()
            ->where('parent_id', $value->id)
            ->orderBy('sequence')
            ->get();

            foreach ($subMenu as $keySm => $valueSm) {
                $fullMenu[] = [
                    'id'        => $valueSm->id,
                    'parentId'  => $valueSm->parent_id,
                    'label'     => $valueSm->label,
                    'url'       => $valueSm->url,
                    'subGroup'  => $valueSm->sub_group,
                    'icon'      => $valueSm->icon,
                    'sequence'  => $valueSm->sequence
                ];
            }
        }

        return $fullMenu;

    }

    public function getUserName($userId) {

        $userName = '';

        $user = UsersTable::query()
        ->where('user_id', $userId)
        ->first();

        if ($user) {
            $userName = $user->user_fname. ' '.$user->user_lname;
        }
        

        return $userName;
    }

    public function getUser($userId) {

        $usersTable = UsersTable::query()
        ->where('user_id', $userId)
        ->first();

        return $usersTable;
    }

    public function getBrokers() {
        return collect($this->index())
            ->filter(function ($user) {
                return in_array($user['trackerView'], ['Broker', 'Manager']);
            })
            ->map(function ($broker) {
                return [
                    'id' => $broker['id'],
                    'fullName' => $broker['fullName'],
                    'companyId' => $broker['companyId'],
                ];
            })
            ->values()
            ->toArray();
    }  

    public function getAccountingUsers() {
        return collect($this->index())
            ->filter(function ($user) {
                return $user['trackerView'] === 'Accounting';
            })
            ->map(function ($accountingUsers) {
                return [
                    'id' => $accountingUsers['id'],
                    'fullName' => $accountingUsers['fullName'],
                ];
            })
            ->values()
            ->toArray();
    }  

    public function getFundingUsers() {

        $usersObj = UsersTable::query()
            ->where('inuse', 'yes')
            ->where('funding', 'yes')
            ->orderBy('user_fname')
            ->get();

        $users = array();
        if ($usersObj) {
            foreach ($usersObj as $key => $value) {
                $users[] = [
                    'id' => $value->user_id,
                    'username' => $value->user_name,
                    'fullName' => $value->user_fname . ' ' . $value->user_lname,
                    'firstName' => $value->user_fname,
                    'lastName' => $value->user_lname,
                    'email' => $value->user_email,
                    'userDepartment' => $value->user_dept,
                    'trackerView' => $value->doctracker_view,
                    'isAdmin' => $value->admin,
                    'companyId' => $value->default_company_id
                ];
            }
        }

        return $users;
    }

    public function getSupportUsers() {
        return collect($this->index())
            ->filter(function ($user) {
                return $user['trackerView'] === 'Support';
            })
            ->map(function ($supportUsers) {
                return [
                    'id' => $supportUsers['id'],
                    'fullName' => $supportUsers['fullName'],
                    'email' => $supportUsers['email']
                ];
            })
            ->values()
            ->toArray();
    }

    public function getSequenceAgents() {

        $usersTable = UsersTable::query()
        ->where('inuse', 'yes')
        ->where(function ($query) {
            $query->where('is_bdm', 'yes')
                ->orWhere('is_underwriting_assistant', 'yes');
        })
        ->orderBy('user_fname')
        ->get();

        $agentOptions = array();
        foreach ($usersTable as $key => $value) {
            if(!empty($value->user_name)) {    
                $agentOptions[] = [
                    'id' => $value->user_id,
                    'username' => $value->user_name,
                    'fullName' => $value->user_fname . ' ' . $value->user_lname,
                    'isBdm' => $value->is_bdm,
                    'isUnderwritingAssistant' => $value->is_underwriting_assistant
                ];
            }
        }

        return $agentOptions;
    }

    public function getSequenceAgentList() {

        $userId = Auth::user()->user_id ?? 99;

        $user = UsersTable::query()
        ->where('user_id', $userId)
        ->first();

        $defaultCompanyId = 99;
        $admin = 'No';
        $agentId = 99;

        if ($user) {
            $defaultCompanyId = $user->default_company_id;
            $admin = $user->admin;
        }

        $query = UsersTable::query();

        if ($defaultCompanyId == 301 && $admin == "no") {

            $query->where('default_company_id', 301)
                ->where(function ($q) use ($agentId) {
                    $q->where('inuse', 'yes')
                        ->orWhere(function ($q2) use ($agentId) {
                            $q2->where('inuse', 'no')->where('user_id', $agentId);
                        });
                });

        } elseif ($defaultCompanyId == 601 && $admin == "no") {

            $query->where('default_company_id', 601)
                ->where(function ($q) use ($agentId) {
                    $q->where('inuse', 'yes')
                        ->orWhere(function ($q2) use ($agentId) {
                            $q2->where('inuse', 'no')->where('user_id', $agentId);
                        });
                });

        } elseif ($defaultCompanyId == 701 && $admin == "no") {

            $query->where('default_company_id', 701)
                ->where(function ($q) use ($agentId) {
                    $q->where('inuse', 'yes')
                        ->orWhere(function ($q2) use ($agentId) {
                            $q2->where('inuse', 'no')->where('user_id', $agentId);
                        });
                });

        } elseif ($defaultCompanyId == 401 && $admin == "no") {

            $query->where('default_company_id', 401)
                ->where(function ($q) use ($agentId) {
                    $q->where('inuse', 'yes')
                        ->orWhere(function ($q2) use ($agentId) {
                            $q2->where('inuse', 'no')->where('user_id', $agentId);
                        });
                });

        } elseif ($admin == "no") {

            $query->whereNotIn('default_company_id', [301, 601, 401])
                ->where('inuse', 'yes');

        } else {

            $query->where(function ($q) use ($agentId) {
                $q->where('inuse', 'yes')
                ->orWhere(function ($q2) use ($agentId) {
                    $q2->where('inuse', 'no')->where('user_id', $agentId);
                });
            });
        }

        $queryAgents = $query->orderBy('user_name')->get();        
        

        $sequenceAgentList = array();

        foreach ($queryAgents as $key => $value) {
            if (!empty($value->user_fname)) {
                $sequenceAgentList[] = [
                    'id' => $value->user_id,
                    'name' => $value->user_fname . ' ' . $value->user_lname,
                ];
            }
        }

        return $sequenceAgentList;
    }


    public function getSigningAgents() {

        $usersTable = UsersTable::query()
        ->where('inuse', 'yes')
        ->orderBy('user_fname')
        ->get();

        $signingAgentOptions = array();
        $signingAgentOptions[] = [
            'id' => 0,
            'name' => '',
        ];

        foreach ($usersTable as $key => $value) {
            if (!empty($value->user_fname)) {
                $signingAgentOptions[] = [
                    'id' => $value->user_id,
                    'name' => $value->user_fname . ' ' . $value->user_lname,
                ];
            }
        }

        return $signingAgentOptions;
    }

    public function getAllAgents() {

        $usersTable = UsersTable::query()
        ->where('inuse', 'yes')
        ->where('doctracker_view','Broker')
        ->where('default_company_id',1)
        ->orderBy('user_fname')
        ->get();

        $agentOptions = array();
        $agentOptions[] = [
            'id' => 0,
            'name' => '',
        ];

        foreach ($usersTable as $key => $value) {
            if (!empty($value->user_fname)) {
                $agentOptions[] = [
                    'id' => $value->user_id,
                    'name' => $value->user_fname . ' ' . $value->user_lname,
                ];
            }
        }

        return $agentOptions;
    }
}