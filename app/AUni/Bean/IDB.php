<?php

namespace App\AUni\Bean;

interface IDB {

    public function beginTransaction();
    
    public function rollback();

    public function commit();

    public function select($query, $fields = []);

    public function insert($table, $fields);

    public function insertGetId($table, $fields, $idName);

    public function update($table, $fields, $conditions);

    public function delete($table, $conditions);

    public function query($query);

    public function statement($query);

    public function save($table, $fields, $conditions, $idName);

}
