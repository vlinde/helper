<?php

namespace vlinde\helper\Facades;

use Illuminate\Support\Facades\Facade;

class helper extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'helper';
    }
}
