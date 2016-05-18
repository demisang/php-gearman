<?php

namespace demi\gearman\laravel5;

use Illuminate\Support\Facades\Facade;

/**
 * Gearman facade
 */
class GearmanFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'gearman';
    }
}
