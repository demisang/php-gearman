<?php
/**
 * @copyright Copyright (c) 2018 Ivan Orlov
 * @license   https://github.com/demisang/php-gearman/blob/master/LICENSE
 * @link      https://github.com/demisang/php-gearman#readme
 * @author    Ivan Orlov <gnasimed@gmail.com>
 */

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
        $this->mergeConfigFrom(
            $this->config_path('gearman.php'), 'gearman'
        );

        $this->app->singleton('gearman', function ($app) {
            if (!$this->isLumen()) {
                $host = config('gearman.host', '127.0.0.1');
                $port = config('gearman.port', 4730);
                $servers = config('gearman.servers', []);
            } else {
                $gearman = $this->app['config']->get('gearman');
                $host = !empty($gearman['host']) ? $gearman['host'] :'127.0.0.1';
                $port = !empty($gearman['port']) ? $gearman['port'] :4730;
                $servers = !empty($gearman['servers']) ? $gearman['servers'] : [];
            }
            $component = new \demi\gearman\GearmanQueue($host, $port, $servers);
            $component->beforeJobCallback = config('gearman.beforeJobCallback');
            $component->afterJobCallback = config('gearman.afterJobCallback');

            return $component;
        });


        $this->app->singleton('command.gearman',
            function ($app) {
                return new \demi\gearman\laravel5\Console\SupervisorCommand();
            }
        );
        $this->commands('command.gearman');
    }

    public function boot()
    {
        if (!$this->isLumen()) {
            $this->publishes([
                $this->getConfigPath() => config_path('gearman.php'),
            ], 'config');
        }
    }

    /**
     * Get the configuration path.
     *
     * @param  string $path
     * @return string
     */
    private function config_path($path = '')
    {
        if (!$this->isLumen()) {
            return config_path($path);
        }
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
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

    /**
     * @return bool
     */
    protected function isLumen()
    {
        return str_contains($this->app->version(), 'Lumen');
    }
}
