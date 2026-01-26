<?php

namespace App\Amur\Factory;

class Factory {

    /**
     * @deprecated
     *
     * @return $this
     */
    public static function create($class, $db = null, $logger = null, $id = null) {
        $className = 'App\\Amur\\' . $class;

        if(is_null($db) && is_null($logger) && is_null($id)) {
            return new $className();

        } elseif(!is_null($db) && !is_null($logger) && !is_null($id)) {
            return new $className($db, $logger, $id);

        } else {
            return new $className($db, $logger);
        }
    }

}