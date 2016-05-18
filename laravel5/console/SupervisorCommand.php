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
    protected $description = 'Generate a new IDE Helper file.';

    /**
     * Execute the console command.
     * Request user supervisor config set.
     */
    public function fire()
    {
        \Gearman::configureSupervisor();
    }
}