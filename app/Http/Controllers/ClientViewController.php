<?php

namespace App\Http\Controllers;

use App\Models\UsersTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientViewController extends Controller {

    // Ativa visão de um cliente específico
    public function set(Request $request, $companyId) {

        $user = Auth::user();

        if (!$user || $user->is_uni_user !== 'S') {
            return response()->json(['status' => 'error', 'message' => 'Sem permissão.'], 403);
        }

        UsersTable::where('user_id', $user->user_id)
            ->update(['active_company_id' => $companyId]);

        return response()->json(['status' => 'success', 'message' => '']);
    }

    // Desativa visão do cliente (volta ao normal)
    public function clear(Request $request) {

        $user = Auth::user();

        if (!$user || $user->is_uni_user !== 'S') {
            return response()->json(['status' => 'error', 'message' => 'Sem permissão.'], 403);
        }

        UsersTable::where('user_id', $user->user_id)
            ->update(['active_company_id' => null]);

        return response()->json(['status' => 'success', 'message' => '']);
    }
}
