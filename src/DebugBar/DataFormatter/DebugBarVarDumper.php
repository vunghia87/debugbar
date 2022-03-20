<?php

namespace DebugBar\DataFormatter;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\Dumper\Dumper;

class DebugBarVarDumper implements AssetProvider
{
    /** @var Dumper */
    protected $dumper;

    protected function getDumper()
    {
        if (!$this->dumper) {
            $this->dumper = (new Dumper())
                ->depth(3)
                ->filter(true)
                ->onlyVar(true)
                ->type('html');
        }
        return $this->dumper;
    }

    public function renderVar($data)
    {
        return $this->getDumper()->dump($data);
    }

    public function getAssets() {
        $dumper = $this->getDumper();
        return array(
            'inline_head' => array(
                'html_var_dumper' => $dumper->getDumpHeader(),
            ),
        );
    }
}
