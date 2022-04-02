<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataCollector;

/**
 * Collects auth [Admin,User,Lender] to BSM
 */
class AuthCollector extends DataCollector implements Renderable
{
    protected $name = 'auth';

    public function collect()
    {
        $auth = \DebugBar\Loader\AuthGlobal::getAuth();

        if (empty($auth)) {
            return [];
        }

        $class = is_object($auth) ? get_class($auth) : 'Auth';
        $auth = $this->getVarDumper()->renderVar($auth);
        return [$class => $auth];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getWidgets()
    {
        $name = $this->getName();
        return array(
            "$name" => array(
                "icon" => "user-secret",
                "widget" => 'PhpDebugBar.Widgets.VariableListWidget',
                "map" => "$name",
                "default" => "{}"
            )
        );
    }
}
