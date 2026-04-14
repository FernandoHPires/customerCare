<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\Models\GroupMembersTable;
use App\Models\UsersTable;
use App\Models\Clientes;
use App\Models\GroupScreen;
use App\Models\Menu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserBO {

    private $logger;
    private $db;
    private $menuList = [];

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function index() {

        $usersObj = UsersTable::query()
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
                'fullName' => $userObj->user_fname . ' ' . $userObj->user_lname,
                'firstName' => $userObj->user_fname,
                'lastName' => $userObj->user_lname,
                'email' => $userObj->user_email,
                'trackerManager' => '',
                'trackerView' => '',
                'isAdmin' => '',
                'companyId' => $userObj->default_company_id
            ];
        }

        return $user;
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

    public function getUsers() {

        $usersObj = UsersTable::query()
            ->leftJoin('clientes', 'clientes.id', '=', 'users_table.default_company_id')
            ->select('users_table.*', 'clientes.nome as company_name')
            ->orderBy('users_table.user_fname')
            ->get();

        $users = [];
        foreach ($usersObj as $value) {
            $users[] = [
                'id'          => $value->user_id,
                'username'    => $value->user_name,
                'firstName'   => $value->user_fname,
                'lastName'    => $value->user_lname,
                'fullName'    => $value->user_fname . ' ' . $value->user_lname,
                'email'       => $value->user_email,
                'phone'       => $value->user_phone,
                'dept'        => $value->user_dept,
                'companyId'   => $value->default_company_id,
                'companyName' => $value->company_name,
                'isAdmin'     => $value->admin,
            ];
        }

        return $users;
    }

    public function saveUser($fields) {

        $this->logger->info('UserBO->saveUser', [$fields]);

        $loggedUserId = Auth::user()->user_id;

        DB::beginTransaction();

        try {

            if ($fields->action == 'Adicionar') {
                $user = new UsersTable();
                $user->created_by    = $loggedUserId;
                $user->user_password = Hash::make($fields->password);
            } else {
                $user = UsersTable::find($fields->id);
                if (!$user) {
                    return false;
                }
                $user->updated_by = $loggedUserId;
                if (!empty($fields->password)) {
                    $user->user_password = Hash::make($fields->password);
                }
            }

            $user->user_name           = $fields->username;
            $user->user_fname          = $fields->firstName;
            $user->user_lname          = $fields->lastName;
            $user->user_email          = $fields->email;
            $user->user_phone          = $fields->phone ?? '';
            $user->user_dept           = $fields->dept ?? '';
            $user->default_company_id  = $fields->companyId;
            $user->admin               = $fields->isAdmin ? 1 : 0;
            $user->save();

            DB::commit();
            return $user->user_id;

        } catch (\Throwable $e) {

            $this->logger->info('UserBO->saveUser', [$e->getMessage(), $e->getTraceAsString()]);
            DB::rollback();
            return false;
        }
    }

    public function deleteUser($id) {

        $this->logger->info('UserBO->deleteUser', ['id' => $id]);

        $loggedUserId = Auth::user()->user_id;

        DB::beginTransaction();

        try {
            $user = UsersTable::find($id);
            if (!$user) {
                return false;
            }
            $user->deleted_by = $loggedUserId;
            $user->save();
            $user->delete();

            DB::commit();
            return true;

        } catch (\Throwable $e) {

            $this->logger->info('UserBO->deleteUser', [$e->getMessage(), $e->getTraceAsString()]);
            DB::rollback();
            return false;
        }
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

}