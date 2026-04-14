<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\Models\Perfis;
use App\Models\PerfilMenu;
use App\Models\Menu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PermissaoBO {

    private $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function getPermissoes() {

        $this->logger->info('PermissaoBO->getPermissoes', []);

        $perfis = Perfis::orderBy('nome')->get();

        $result = [];
        foreach ($perfis as $perfil) {
            $qtdMenus = PerfilMenu::where('perfil_id', $perfil->id)
                ->whereNull('deleted_at')
                ->count();

            $result[] = [
                'id'        => $perfil->id,
                'nome'      => $perfil->nome,
                'descricao' => $perfil->descricao,
                'qtdMenus'  => $qtdMenus,
            ];
        }

        return $result;
    }

    public function getMenusPerfil($perfilId) {

        $this->logger->info('PermissaoBO->getMenusPerfil', ['perfilId' => $perfilId]);

        // IDs de menus que este perfil já tem acesso
        $perfilMenuIds = PerfilMenu::where('perfil_id', $perfilId)
            ->whereNull('deleted_at')
            ->pluck('menu_id')
            ->toArray();

        // Busca todos os módulos pai
        $parents = Menu::whereNull('parent_id')
            ->whereNull('deleted_at')
            ->orderBy('sequence')
            ->get();

        // Busca todos os submenus
        $allSubMenus = Menu::whereNotNull('parent_id')
            ->whereNull('deleted_at')
            ->orderBy('sequence')
            ->get();

        $result = [];
        foreach ($parents as $parent) {

            $children = [];
            foreach ($allSubMenus as $menu) {
                if ($menu->parent_id == $parent->id) {
                    $children[] = [
                        'id'      => $menu->id,
                        'label'   => $menu->label,
                        'checked' => in_array($menu->id, $perfilMenuIds),
                    ];
                }
            }

            if (!empty($children)) {
                // Módulo com submenus → mostra como grupo
                $result[] = [
                    'id'    => $parent->id,
                    'label' => $parent->label,
                    'icon'  => $parent->icon,
                    'menus' => $children,
                ];
            } elseif (!empty($parent->url)) {
                // Menu direto sem submenus mas com URL → aparece como item único
                $result[] = [
                    'id'    => $parent->id,
                    'label' => $parent->label,
                    'icon'  => $parent->icon,
                    'menus' => [
                        [
                            'id'      => $parent->id,
                            'label'   => $parent->label,
                            'checked' => in_array($parent->id, $perfilMenuIds),
                        ]
                    ],
                ];
            }
        }

        return $result;
    }

    public function savePermissao($perfilId, $menuIds) {

        $this->logger->info('PermissaoBO->savePermissao', ['perfilId' => $perfilId, 'menuIds' => $menuIds]);

        $userId = Auth::user()->user_id;

        DB::beginTransaction();

        try {

            // Remove todos os vínculos atuais (hard delete pois é tabela pivot)
            PerfilMenu::where('perfil_id', $perfilId)->forceDelete();

            // Re-insere apenas os selecionados
            foreach ($menuIds as $menuId) {
                PerfilMenu::create([
                    'perfil_id'  => $perfilId,
                    'menu_id'    => $menuId,
                    'created_by' => $userId,
                ]);
            }

            DB::commit();
            return true;

        } catch (\Throwable $e) {

            $this->logger->info('PermissaoBO->savePermissao', [$e->getMessage(), $e->getTraceAsString()]);
            DB::rollback();
            return false;
        }
    }
}
