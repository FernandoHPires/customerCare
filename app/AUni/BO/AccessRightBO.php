<?php

namespace App\AUni\BO;

use App\Models\Menu;
use App\Models\PerfilMenu;
use Illuminate\Support\Facades\Auth;

class AccessRightBO {

    /**
     * Verifica se o usuário autenticado tem acesso ao menu com o path informado.
     *
     * Regras:
     *  - Funcionários UNI (is_uni_user = 'S') sempre têm acesso.
     *  - Demais usuários precisam ter o menu vinculado ao seu perfil_id.
     */
    public static function check(string $path): bool {

        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Busca o menu pelo path (ex: '/clientes', '/usuarios')
        $menu = Menu::whereNull('deleted_at')
            ->where('url', $path)
            ->first();

        if (!$menu) {
            return false;
        }

        // Verifica se o perfil do usuário tem acesso a esse menu
        return PerfilMenu::where('perfil_id', $user->perfil_id)
            ->where('menu_id', $menu->id)
            ->whereNull('deleted_at')
            ->exists();
    }
}
