<?php

namespace demi\gearman\laravel5;

/**
 * Gearman facade
 */
class GearmanFacade extends \Illuminate\Support\Facades\Facade
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
