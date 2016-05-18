<?php

namespace demi\gearman\laravel5;

use demi\gearman\GearmanQueue;
use demi\gearman\SupervisorConfig;

/**
 * Gearman aueue service provider
 */
class GearmanServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * @inheritdoc
     */
    public function register()
    {
        $this->app->singleton('gearman-queue', function ($app) {
            $config = config('gearman');
            $component = new \demi\gearman\GearmanQueue($config['host'], $config['port'], $config['servers']);
            $component->beforeJobCallback = $config['beforeJobCallback'];
            $component->afterJobCallback = $config['afterJobCallback'];

            return $component;
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/gearman.php' => base_path('config/gearman'),
        ], 'config');
    }

    /**
     * Run supervisor configurator
     */
    public static function configureSupervisor()
    {
        $config = config('gearman.supervisorConfig');
        $supervisor = new SupervisorConfig($config['configFile'], $config['workersDirectory']);
        $supervisor->restartSleepingTime = $config['restartSleepingTime'];
        $supervisor->workersConfig = $config['all'];
        $supervisor->workersSets = $config['sets'];

        $supervisor->requestUpdate();
    }
}