<?php

namespace Iquesters\Dev\Config;

use Iquesters\Foundation\Support\BaseConf;
use Iquesters\Foundation\Enums\Module;

class DevConf extends BaseConf
{
    // Inherited property of BaseConf, must initialize
    protected ?string $identifier = Module::DEV;
    
    protected function prepareDefault(BaseConf $default_values)
    {

    }
}