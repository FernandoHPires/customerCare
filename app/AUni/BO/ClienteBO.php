<?php

namespace App\AUni\BO;

use App\AUni\Bean\ILogger;
use App\AUni\Bean\IDB;
use Illuminate\Support\Facades\Auth;
use App\Models\Clientes;

class ClienteBO {

    private $logger;
    private $db;

    public function __construct(ILogger $logger, IDB $db) {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getClientes() {

        $this->logger->info('ClienteBO->getClientes', []);

        $clientes = Clientes::orderBy('nome', 'asc')->get();

        $result = [];
        foreach ($clientes as $c) {
            $result[] = [
                'id'           => $c->id,
                'nome'         => $c->nome,
                'nomeFantasia' => $c->nome_fantasia,
                'cnpj'         => $c->cnpj,
                'email'        => $c->email,
                'telefone'     => $c->telefone,
                'status'       => $c->status,
            ];
        }

        return $result;
    }

    public function saveCliente($fields) {

        $this->logger->info('ClienteBO->saveCliente', [$fields]);

        $userId = Auth::user()->user_id;

        $this->db->beginTransaction();

        try {

            if ($fields->action == 'Adicionar') {
                $cliente = new Clientes();
                $cliente->created_by = $userId;
            } else {
                $cliente = Clientes::find($fields->id);
                if (!$cliente) {
                    return false;
                }
                $cliente->updated_by = $userId;
            }

            $cliente->nome         = $fields->nome;
            $cliente->nome_fantasia = $fields->nomeFantasia;
            $cliente->cnpj         = $fields->cnpj;
            $cliente->email        = $fields->email;
            $cliente->telefone     = $fields->telefone;
            $cliente->status       = $fields->status ?? 'A';
            $cliente->save();

            $this->db->commit();
            return $cliente->id;

        } catch (\Throwable $e) {

            $this->logger->info('ClienteBO->saveCliente', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
    }

    public function deleteCliente($id) {

        $this->logger->info('ClienteBO->deleteCliente', ['id' => $id]);

        $userId = Auth::user()->user_id;

        $this->db->beginTransaction();

        try {
            $cliente = Clientes::find($id);
            if (!$cliente) {
                return false;
            }
            $cliente->deleted_by = $userId;
            $cliente->save();
            $cliente->delete();

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {

            $this->logger->info('ClienteBO->deleteCliente', [$e->getMessage(), $e->getTraceAsString()]);
            $this->db->rollback();
            return false;
        }
    }
}
