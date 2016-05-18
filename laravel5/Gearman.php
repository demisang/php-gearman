<?php

namespace demi\gearman\laravel5;

/**
 * Class Gearman
 */
class Gearman extends \Illuminate\Support\Facades\Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'gearman-queue';
    }
}
