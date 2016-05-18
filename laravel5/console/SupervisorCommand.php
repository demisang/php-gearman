<?php

namespace demi\gearman\laravel5\console;

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
        \Gearman::configureSupervisor();
    }
}