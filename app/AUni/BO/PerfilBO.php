<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\AUni\Bean\IDB;
use App\Models\Perfis;
use Illuminate\Support\Facades\Auth;

class PerfilBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getPerfis() {

        $perfis = Perfis::orderBy('nome')->get();

        $result = [];
        foreach ($perfis as $p) {
            $result[] = [
                'id'        => $p->id,
                'nome'      => $p->nome,
                'descricao' => $p->descricao,
            ];
        }

        return $result;
    }

    public function savePerfil($fields) {

        $this->logger->info('PerfilBO->savePerfil', [$fields]);

        $userId = Auth::user()->user_id;

        $this->db->beginTransaction();

        try {

            if ($fields->action == 'Adicionar') {
                $perfil = new Perfis();
                $perfil->created_by = $userId;
            } else {
                $perfil = Perfis::find($fields->id);
                if (!$perfil) {
                    return false;
                }
                $perfil->updated_by = $userId;
            }

            $perfil->nome      = $fields->nome;
            $perfil->descricao = $fields->descricao ?? null;
            $perfil->save();

            $this->db->commit();
            return $perfil->id;

        } catch (\Throwable $e) {

            $this->logger->info('PerfilBO->savePerfil', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
    }

    public function deletePerfil($id) {

        $this->logger->info('PerfilBO->deletePerfil', ['id' => $id]);

        $userId = Auth::user()->user_id;

        $this->db->beginTransaction();

        try {
            $perfil = Perfis::find($id);
            if (!$perfil) {
                return false;
            }
            $perfil->deleted_by = $userId;
            $perfil->save();
            $perfil->delete();

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {

            $this->logger->info('PerfilBO->deletePerfil', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
    }
}
