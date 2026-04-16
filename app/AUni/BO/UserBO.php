<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\Models\UsersTable;
use App\Models\Clientes;
use App\Models\Perfis;
use App\Models\PerfilMenu;
use App\Models\Menu;
use App\Models\PasswordHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


class UserBO {

    private $logger;
    private $menuList = [];

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function index() {

        $usersObj = UsersTable::query()
            ->orderBy('user_fname')
            ->get();

        $users = [];
        foreach ($usersObj as $value) {
            $users[] = [
                'id'             => $value->user_id,
                'username'       => $value->user_name,
                'fullName'       => $value->user_fname . ' ' . $value->user_lname,
                'firstName'      => $value->user_fname,
                'lastName'       => $value->user_lname,
                'email'          => $value->user_email,
                'userDepartment' => $value->user_dept,
                'isAdmin'        => $value->admin,
                'companyId'      => $value->default_company_id,
                'perfilId'       => $value->perfil_id,
            ];
        }

        return $users;
    }

    public function show($id) {

        $userObj = UsersTable::query()
            ->where('user_id', $id)
            ->first();

        if (!$userObj) {
            return [];
        }

        return [
            'id'           => $userObj->user_id,
            'username'     => $userObj->user_name,
            'fullName'     => $userObj->user_fname . ' ' . $userObj->user_lname,
            'firstName'    => $userObj->user_fname,
            'lastName'     => $userObj->user_lname,
            'email'        => $userObj->user_email,
            'isAdmin'           => $userObj->admin,
            'companyId'         => $userObj->default_company_id,
            'perfilId'          => $userObj->perfil_id,
            'resetRequest'      => (bool) $userObj->reset_request,
            'twoFactorEnabled'  => (bool) $userObj->two_factor_enabled,
        ];
    }

    public function store($id, $username, $firstName, $lastName, $email) {

        $user = UsersTable::find($id);

        if ($user) {
            $user->user_name  = $username;
            $user->user_fname = $firstName;
            $user->user_lname = $lastName;
            $user->user_email = $email;
            $user->save();
        } else {
            $user = new UsersTable();
            $user->user_password = ' ';
            $user->user_phone    = ' ';
            $user->user_name     = $username;
            $user->user_fname    = $firstName;
            $user->user_lname    = $lastName;
            $user->user_email    = $email;
            $user->save();
        }

        return true;
    }

    public function destroy($id) {

        $user = UsersTable::find($id);

        if ($user) {
            $user->save();
            return true;
        }

        return false;
    }

    public function getUsers() {

        $usersObj = UsersTable::query()
            ->leftJoin('clientes', 'clientes.id', '=', 'users_table.default_company_id')
            ->leftJoin('perfis', 'perfis.id', '=', 'users_table.perfil_id')
            ->select('users_table.*', 'clientes.nome as company_name', 'perfis.nome as perfil_nome')
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
                'perfilId'    => $value->perfil_id,
                'perfilNome'  => $value->perfil_nome,
                'isAdmin'          => $value->admin,
                'twoFactorEnabled' => (bool) $value->two_factor_enabled,
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

                // Valida força da senha
                $validacao = $this->validarForcaSenha($fields->password);
                if (!$validacao['ok']) {
                    return ['ok' => false, 'message' => $validacao['message']];
                }

                $user = new UsersTable();
                $user->created_by    = $loggedUserId;
                $user->user_password = Hash::make($fields->password);
                $user->reset_request = 1; // Força troca de senha no primeiro acesso

            } else {
                $user = UsersTable::find($fields->id);
                if (!$user) {
                    return false;
                }
                $user->updated_by = $loggedUserId;

                if (!empty($fields->password)) {

                    // Valida força da senha
                    $validacao = $this->validarForcaSenha($fields->password);
                    if (!$validacao['ok']) {
                        return ['ok' => false, 'message' => $validacao['message']];
                    }

                    // Verifica se a nova senha é igual à senha atual
                    if (Hash::check($fields->password, $user->user_password)) {
                        return ['ok' => false, 'message' => 'A nova senha não pode ser igual à senha atual.'];
                    }

                    // Verifica histórico das últimas 5 senhas
                    $historico = PasswordHistory::where('user_id', $user->user_id)
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get();

                    foreach ($historico as $registro) {
                        if (Hash::check($fields->password, $registro->password_hash)) {
                            return ['ok' => false, 'message' => 'A nova senha não pode ser igual a uma das últimas 5 senhas utilizadas.'];
                        }
                    }

                    // Salva senha antiga no histórico
                    PasswordHistory::create([
                        'user_id'       => $user->user_id,
                        'password_hash' => $user->user_password,
                        'created_at'    => now(),
                        'created_by'    => $loggedUserId,
                    ]);

                    // Gera hash da nova senha
                    $novoHash = Hash::make($fields->password);

                    // Salva nova senha no histórico também
                    PasswordHistory::create([
                        'user_id'       => $user->user_id,
                        'password_hash' => $novoHash,
                        'created_at'    => now(),
                        'created_by'    => $loggedUserId,
                    ]);

                    $user->user_password = $novoHash;
                    $user->reset_request = 1; // Força troca de senha na próxima entrada
                }
            }

            $user->user_name          = $fields->username;
            $user->user_fname         = $fields->firstName;
            $user->user_lname         = $fields->lastName;
            $user->user_email         = $fields->email;
            $user->user_phone         = $fields->phone ?? '';
            $user->user_dept          = $fields->dept ?? '';
            $user->default_company_id = $fields->companyId;
            $user->perfil_id          = $fields->perfilId ?? null;
            $user->admin              = $fields->isAdmin ? 1 : 0;
            $user->two_factor_enabled = $fields->twoFactorEnabled ? 1 : 0;
            $user->save();

            DB::commit();
            return $user->user_id;

        } catch (\Throwable $e) {

            $this->logger->info('UserBO->saveUser', [$e->getMessage(), $e->getTraceAsString()]);
            DB::rollback();
            return false;
        }
    }

    private function validarForcaSenha($senha) {
        if (strlen($senha) < 8)
            return ['ok' => false, 'message' => 'A senha deve ter no mínimo 8 caracteres.'];
        if (!preg_match('/[A-Z]/', $senha))
            return ['ok' => false, 'message' => 'A senha deve conter pelo menos uma letra maiúscula.'];
        if (!preg_match('/[0-9]/', $senha))
            return ['ok' => false, 'message' => 'A senha deve conter pelo menos um número.'];
        if (!preg_match('/[^A-Za-z0-9]/', $senha))
            return ['ok' => false, 'message' => 'A senha deve conter pelo menos um caractere especial (!@#$%...).'];
        return ['ok' => true, 'message' => ''];
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

    public function getCurrentUserGroups($id) {

        $user = UsersTable::query()
            ->where('user_id', $id)
            ->first();

        if (!$user) {
            return false;
        }

        $perfil = $user->perfil_id ? Perfis::find($user->perfil_id) : null;

        return [
            'id'        => $user->user_id,
            'userName'  => $user->user_name,
            'fullName'  => $user->user_fname . ' ' . $user->user_lname,
            'companyId' => $user->default_company_id,
            'email'     => $user->user_email,
            'perfilId'  => $user->perfil_id,
            'perfilNome'=> $perfil ? $perfil->nome : null,
        ];
    }

    public function getUsersByGroups($groupIds) {

        $usersObj = UsersTable::query()
            ->orderBy('user_fname')
            ->get();

        $users = [];
        foreach ($usersObj as $value) {
            $users[] = [
                'id'        => $value->user_id,
                'userName'  => $value->user_name,
                'fullName'  => $value->user_fname . ' ' . $value->user_lname,
                'companyId' => $value->default_company_id,
                'email'     => $value->user_email,
                'perfilId'  => $value->perfil_id,
            ];
        }

        return $users;
    }

    public function getMenus($userId) {

        $this->logger->info('UserBO->getMenus', [$userId]);

        $fullMenu = $this->getMenu();

        $user = UsersTable::find($userId);

        if (!$user || !$user->perfil_id) {
            return $this->buildMenuStructure([], $fullMenu);
        }

        // Busca todos os menu_ids do perfil do usuário
        $perfilMenuIds = PerfilMenu::query()
            ->where('perfil_id', $user->perfil_id)
            ->whereNull('deleted_at')
            ->pluck('menu_id')
            ->toArray();

        // Adiciona os menus pai automaticamente
        $allMenuIds = [];
        foreach ($perfilMenuIds as $menuId) {
            $menu = Menu::find($menuId);
            if ($menu && $menu->parent_id && !in_array($menu->parent_id, $allMenuIds)) {
                $allMenuIds[] = $menu->parent_id;
            }
            if (!in_array($menuId, $allMenuIds)) {
                $allMenuIds[] = $menuId;
            }
        }

        return $this->buildMenuStructure($allMenuIds, $fullMenu);
    }

    private function buildMenuStructure($userMenuIds, $fullMenu) {

        $filteredMenu = array_filter($fullMenu, function ($item) use ($userMenuIds) {
            return in_array($item['id'], $userMenuIds);
        });

        $menus   = [];
        $menus[] = [
            'path' => '/',
            'name' => 'Home',
            'icon' => 'bi-house-door',
        ];

        foreach ($filteredMenu as $value) {

            if ($value['parentId'] != null) {
                continue;
            }

            $submenus     = [];
            $lastSubGroup = null;

            foreach ($filteredMenu as $valueParent) {
                if ($value['id'] != $valueParent['parentId']) {
                    continue;
                }

                if ($valueParent['subGroup'] != $lastSubGroup && $valueParent['subGroup'] != null && $lastSubGroup != null) {
                    $submenus[] = ['type' => 'divider'];
                }

                $submenus[] = [
                    'type' => 'page',
                    'path' => $valueParent['url'],
                    'name' => $valueParent['label'],
                    'icon' => $valueParent['icon'],
                ];

                $lastSubGroup = $valueParent['subGroup'];
            }

            $url = $value['url'] != '' ? $value['url'] : null;

            if (count($submenus) > 0) {
                $menus[] = [
                    'path'     => $url,
                    'name'     => $value['label'],
                    'icon'     => $value['icon'],
                    'submenus' => $submenus,
                ];
            } else {
                $menus[] = [
                    'path' => $url,
                    'name' => $value['label'],
                    'icon' => $value['icon'],
                ];
            }
        }

        return $menus;
    }

    public function getMenu() {

        $fullMenu = [];

        $menu = Menu::query()
            ->whereNull('parent_id')
            ->orderBy('sequence')
            ->get();

        foreach ($menu as $value) {

            $fullMenu[] = [
                'id'       => $value->id,
                'parentId' => $value->parent_id,
                'label'    => $value->label,
                'url'      => $value->url,
                'subGroup' => $value->sub_group,
                'icon'     => $value->icon,
                'sequence' => $value->sequence,
            ];

            $subMenu = Menu::query()
                ->where('parent_id', $value->id)
                ->orderBy('sequence')
                ->get();

            foreach ($subMenu as $valueSm) {
                $fullMenu[] = [
                    'id'       => $valueSm->id,
                    'parentId' => $valueSm->parent_id,
                    'label'    => $valueSm->label,
                    'url'      => $valueSm->url,
                    'subGroup' => $valueSm->sub_group,
                    'icon'     => $valueSm->icon,
                    'sequence' => $valueSm->sequence,
                ];
            }
        }

        return $fullMenu;
    }

    public function getUserName($userId) {

        $user = UsersTable::query()
            ->where('user_id', $userId)
            ->first();

        return $user ? $user->user_fname . ' ' . $user->user_lname : '';
    }

    public function getUser($userId) {

        return UsersTable::query()
            ->where('user_id', $userId)
            ->first();
    }

    public function getPerfis() {

        $perfis = Perfis::query()
            ->orderBy('nome')
            ->get();

        $result = [];
        foreach ($perfis as $p) {
            $result[] = [
                'id'       => $p->id,
                'nome'     => $p->nome,
                'descricao'=> $p->descricao,
            ];
        }

        return $result;
    }

    public function getBrokers() {
        return [];
    }

    public function getAccountingUsers() {
        return [];
    }

    public function getSupportUsers() {
        return [];
    }
}
