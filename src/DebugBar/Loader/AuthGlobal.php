<?php

namespace DebugBar\Loader;

class AuthGlobal
{
    public static function getAuth()
    {
        $roles = ['user', 'userLender', 'userAdmin'];
        foreach ($roles as $role) {
            if (!empty($GLOBALS[$role])) {
                return $GLOBALS[$role];
            }
        }
        return [];
    }
}