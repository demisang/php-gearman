<?php

namespace demi\gearman\laravel5;

/**
 * Gearman aueue service provider
 */
class GearmanServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * @inheritdoc
     */
    public function register()
    {
        $this->app->singleton('gearman', function ($app) {
            $config = config('gearman');
            $component = new \demi\gearman\GearmanQueue($config['host'], $config['port'], $config['servers']);
            $component->beforeJobCallback = $config['beforeJobCallback'];
            $component->afterJobCallback = $config['afterJobCallback'];

            return $component;
        });

        $this->app['command.gearman'] = $this->app->share(
            function ($app) {
                return new \demi\gearman\laravel5\console\SupervisorCommand();
            }
        );
        $this->commands('command.gearman');
    }

    public function boot()
    {
        $this->publishes(array(
            __DIR__.'/config/gearman.php' => config_path('gearman.php'),
        ), 'config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('gearman', 'command.gearman');
    }
}