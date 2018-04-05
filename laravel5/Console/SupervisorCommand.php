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
        $commandConfigs = $this->supervisorConfigsCommands($config['commandConfigs']);
        $supervisor = new SupervisorConfig($config['configFile'], $config['workersDirectory']);
        $supervisor->restartSleepingTime = $config['restartSleepingTime'];
        $supervisor->workersConfig = $commandConfigs['all'];
        $supervisor->workersSets = $commandConfigs['sets'];

        $supervisor->requestUpdate();
    }

    /**
     * @param $arrConfis
     * @return array
     */
    private function supervisorConfigsCommands($arrConfis)
    {
        $all = [];
        $general = [];
        $minimal = [];
        $maximal = [];

        foreach ($arrConfis as $config) {
            $all[$config['id']] =  ['numprocs' => 0, 'command' => 'php artisan ' . $config['name']];
            $general[$config['id']] = !empty($config['generalNumber']) ? $config['generalNumber'] : 5;
            $minimal[$config['id']] = !empty($config['minimalNumber']) ? $config['minimalNumber'] : 50;
            $maximal[$config['id']] = !empty($config['minimalMaximal']) ? $config['minimalMaximal'] : 100;
        }

        return [
            'all' => $all,
            'sets' => [
                'general' => $general,
                'minimal' => $minimal,
                'maximal' => $maximal,
            ],
        ];
    }

}
