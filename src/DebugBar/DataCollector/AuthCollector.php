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
        $auth = \DebugBar\Partner\AuthPartner::getAuth();

        if (empty($auth)) {
            return ['Guest'];
        }
        $class = is_object($auth) ? get_class($auth) : 'Auth';
        $auth = $this->getDataFormatter()->formatVar($auth);
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
                "icon" => "gear",
                "widget" => 'PhpDebugBar.Widgets.VariableListWidget',
                "map" => "$name",
                "default" => "{}"
            )
        );
    }
}