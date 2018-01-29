<?php
/**
 * @copyright Copyright (c) 2018 Ivan Orlov
 * @license   https://github.com/demisang/php-gearman/blob/master/LICENSE
 * @link      https://github.com/demisang/php-gearman#readme
 * @author    Ivan Orlov <gnasimed@gmail.com>
 */

namespace demi\gearman;

/**
 * Supervisor config tool
 */
class SupervisorConfig
{
    /**
     * Path to the supervisor config file.
     * "/etc/supervisor/conf.d/workers.conf"
     *
     * @var string
     */
    public $configFile;
    /**
     * Default supervisor worker config
     *
     * @var array
     */
    public $defaultWorkerConfig;
    /**
     * All workers config:
     *
     * [
     *     'crop_image' => ['numprocs' => 0, 'command' => '/usr/bin/php yii workers/crop-image'],
     *     'bad_worker' => ['numprocs' => 0, 'command' => '/usr/bin/php yii workers/bad-worker'],
     *     ...
     * ]
     *
     * @var array
     */
    public $workersConfig = array();
    /**
     * Workers sets list:
     *
     * [
     *     'general' => [
     *         'crop_image' => 5,
     *     ],
     *     'minimal' => [
     *         'crop_image' => 50,
     *         'bad_worker' => 50,
     *     ],
     *     'maximal' => [
     *         'crop_image' => 100,
     *         'bad_worker' => 100,
     *     ],
     * ]
     *
     * @var array
     */
    public $workersSets = array();
    /**
     * Number of seconds before supervisor will started again after config updating.
     * (Some times very big workers number require more time for restart)
     *
     * @var int
     */
    public $restartSleepingTime = 5;

    // Console output colors
    const FG_RED = 31;
    const FG_GREEN = 32;

    /**
     * SupervisorConfig constructor.
     *
     * @param string $configFile       Path to the supervisor config file. ("/etc/supervisor/conf.d/workers.conf")
     * @param string $workersDirectory Directory where placed workers
     */
    public function __construct($configFile, $workersDirectory)
    {
        $this->configFile = $configFile;

        $this->defaultWorkerConfig = array(
            'command' => '',
            'process_name' => '%(program_name)s_%(process_num)02d',
            'numprocs' => 0,
            'directory' => $workersDirectory, // "/var/www/site"
            'stdout_logfile' => '/var/www/logs/workers/%(program_name)s.log',
            'stderr_logfile' => '/var/www/logs/workers/%(program_name)s.error.log',
            'autostart' => 'true',
            'autorestart' => 'true',
            'user' => 'www-data',
            'stopsignal' => 'KILL',
        );
    }

    /**
     * Give user opportunity to choise workers set
     */
    public function requestUpdate()
    {
        // Workers list with basic config set
        $workers = $this->workersConfig;

        // Workers sets
        $sets = $this->workersSets;

        // Show info about available sets
        static::stdout("\nWorkers sets:\n");

        $i = 0;
        $setNumKeys = array();
        foreach ($sets as $name => $config) {
            $i++;
            $setNumKeys[$i] = $name;
            static::stdout($i . '. ' . $name . "\n");
        }

        // Get number of selected set
        $setNum = $this->prompt("\nChoise worker set:", array('required' => true));
        if (!isset($setNumKeys[$setNum])) {
            static::stdout("Set '$setNum' not exists\n", static::FG_RED);

            return;
        }

        // Merge default config with selected config set
        foreach ($sets[$setNumKeys[$setNum]] as $workerId => $numprocs) {
            $workers[$workerId]['numprocs'] = $numprocs;
        }

        static::stdout("\nActive workers:\n");
        // Generate config file lines
        $lines = array();
        foreach ($workers as $workerId => $config) {
            // Merge default config with custom worker config
            $resultConfig = array_merge($this->defaultWorkerConfig, $config);

            // Not need add workers without processes
            if ($resultConfig['numprocs'] <= 0) {
                continue;
            }

            $lines[] = "[program:$workerId]";

            foreach ($resultConfig as $k => $v) {
                $lines[] = "$k=$v";
            }
            $lines[] = ''; // empty line separator

            // Show info about active workers
            if ($resultConfig['numprocs'] > 0) {
                static::stdout("$workerId: " . $resultConfig['numprocs'] . "\n", static::FG_GREEN);
            }
        }

        // Rewrite config file
        $f = @fopen($this->configFile, 'w');
        if (!$f) {
            static::stdout("Cannot open config file '$this->configFile' for write\n", static::FG_RED);

            return;
        }

        // Write new config lines
        foreach ($lines as $line) {
            fwrite($f, $line . PHP_EOL);
        }
        fclose($f);
        static::stdout("\nConfig file saved\n", static::FG_GREEN);

        // Stop supervisor service
        $shellResult = trim(shell_exec('service supervisor stop'));
        static::stdout("Supervisor stopped, waiting $this->restartSleepingTime seconds..." .
            (!empty($shellResult) ? " ($shellResult)" : '') . "\n", static::FG_GREEN);
        // Snooze, because supervisor must rest before work...
        sleep($this->restartSleepingTime);
        // Start supervisor service
        $shellResult = trim(shell_exec('service supervisor start'));
        static::stdout('Supervisor started' . (!empty($shellResult) ? " ($shellResult)" : '') . "\n", static::FG_GREEN);
    }

    /**
     * Prints a string to STDOUT
     *
     * You may optionally format the string with ANSI codes by
     * passing additional parameters using the constants defined in [[\yii\helpers\Console]].
     *
     * Example:
     *
     * ~~~
     * static::stdout('This will be red and underlined.', Console::FG_RED, Console::UNDERLINE);
     * ~~~
     *
     * @param string $string the string to print
     *
     * @return int|boolean Number of bytes printed or false on error
     */
    protected static function stdout($string)
    {
        $args = func_get_args();
        array_shift($args);
        $string = static::ansiFormat($string, $args);

        return fwrite(\STDOUT, $string);
    }

    /**
     * Will return a string formatted with the given ANSI style
     *
     * @param string $string the string to be formatted
     * @param array $format  An array containing formatting values.
     *                       You can pass any of the FG_*, BG_* and TEXT_* constants
     *                       and also [[xtermFgColor]] and [[xtermBgColor]] to specify a format.
     *
     * @return string
     */
    protected static function ansiFormat($string, $format = array())
    {
        $code = implode(';', $format);

        return "\033[0m" . ($code !== '' ? "\033[" . $code . "m" : '') . $string . "\033[0m";
    }

    /**
     * Prompts the user for input and validates it
     *
     * @param string $text   prompt string
     * @param array $options the options to validate the input:
     *
     * - `required`: whether it is required or not
     * - `default`: default value if no input is inserted by the user
     * - `pattern`: regular expression pattern to validate user input
     * - `validator`: a callable function to validate input. The function must accept two parameters:
     * - `input`: the user input to validate
     * - `error`: the error value passed by reference if validation failed.
     *
     * @return string the user input
     */
    public static function prompt($text, $options = array())
    {
        $options = array_merge(array(
            'required' => false,
            'default' => null,
            'pattern' => null,
            'validator' => null,
            'error' => 'Invalid input.',
        ), $options);
        $error = null;

        top:
        $input = $options['default']
            ? static::input("$text [" . $options['default'] . '] ')
            : static::input("$text ");

        if ($input === '') {
            if (isset($options['default'])) {
                $input = $options['default'];
            } elseif ($options['required']) {
                static::stdout($options['error']);
                goto top;
            }
        } elseif ($options['pattern'] && !preg_match($options['pattern'], $input)) {
            static::stdout($options['error']);
            goto top;
        } elseif ($options['validator'] &&
            !call_user_func_array($options['validator'], array($input, &$error))
        ) {
            static::stdout(isset($error) ? $error : $options['error']);
            goto top;
        }

        return $input;
    }

    /**
     * Asks the user for input. Ends when the user types a carriage return (PHP_EOL). Optionally, It also provides a
     * prompt.
     *
     * @param string $prompt the prompt to display before waiting for input (optional)
     *
     * @return string the user's input
     */
    public static function input($prompt = null)
    {
        if (isset($prompt)) {
            static::stdout($prompt);
        }

        return static::stdin();
    }

    /**
     * Gets input from STDIN and returns a string right-trimmed for EOLs.
     *
     * @param boolean $raw If set to true, returns the raw string without trimming
     *
     * @return string the string read from stdin
     */
    public static function stdin($raw = false)
    {
        return $raw ? fgets(\STDIN) : rtrim(fgets(\STDIN), PHP_EOL);
    }
}
