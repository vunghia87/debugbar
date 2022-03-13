<?php

namespace DebugBar\Partner;

class AuthPartner
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