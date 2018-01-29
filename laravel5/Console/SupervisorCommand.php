<?php
/**
 * @copyright Copyright (c) 2018 Ivan Orlov
 * @license   https://github.com/demisang/php-gearman/blob/master/LICENSE
 * @link      https://github.com/demisang/php-gearman#readme
 * @author    Ivan Orlov <gnasimed@gmail.com>
 */

namespace demi\gearman\laravel5\Console;

use demi\gearman\SupervisorConfig;

/**
 * Supervisor config command
 */
class SupervisorCommand extends \Illuminate\Console\Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'gearman';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run supervisor configurator';

    /**
     * Execute the console command.
     * Request user supervisor config set.
     */
    public function fire()
    {
        $config = config('gearman.supervisorConfig');
        $supervisor = new SupervisorConfig($config['configFile'], $config['workersDirectory']);
        $supervisor->restartSleepingTime = $config['restartSleepingTime'];
        $supervisor->workersConfig = $config['all'];
        $supervisor->workersSets = $config['sets'];

        $supervisor->requestUpdate();
    }
}
