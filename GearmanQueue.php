<?php

namespace demi\gearman;

use GearmanWorker;
use GearmanClient;

/**
 * Gearman queue class
 */
class GearmanQueue
{
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';

    /**
     * Gearman server host
     *
     * @var string
     */
    public $host = '127.0.0.1';
    /**
     * Gearman server port
     *
     * @var int
     */
    public $port = 4730;
    /**
     * Addtitional gearman servers
     *
     * @var array array('123.134.156.245:4730', '178.214.52.184:4730'), ...
     */
    public $servers = array();
    /**
     * Callacble function, running on before worker job handler call
     * function ($jobName, \GearmanJob $job) {
     *     $workload = $job->workload();
     *     var_dump($jobName);  // worker name, eg.: "crop_image"
     *     var_dump($workload); // job workload string, eg.: '{"post_id":7388,"foo":"bar"}' @see deserializeWorkload()
     * }
     *
     * @var callable
     */
    public $beforeJobCallback;
    /**
     * Callacble function, running on after worker job handler called
     * function ($jobName, \GearmanJob $job, $result) {
     *     $workload = $job->workload();
     *     var_dump($jobName);  // worker name, eg.: "crop_image"
     *     var_dump($workload); // job workload string, eg.: '{"post_id":7388,"foo":"bar"}' @see deserializeWorkload()
     *     var_dump($result);   // job handler return value
     * }
     *
     * @var callable
     */
    public $afterJobCallback;
    /**
     * Gearman client instance
     *
     * @var GearmanClient
     */
    private $_client;
    /**
     * Gearman worker instance
     *
     * @var GearmanWorker
     */
    private $_worker;

    /**
     * GearmanQueue constructor.
     *
     * @param string $host   Gearman job server host
     * @param int $port      Gearman job server post
     * @param array $servers Additional Gearman job servers: array('123.134.156.245:4730', '178.214.52.184:4730'), ...
     */
    public function __construct($host = '127.0.0.1', $port = 4730, $servers = array())
    {
        $this->host = $host;
        $this->port = $port;
        $this->servers = $servers;
    }

    /**
     * Get gearman client instance
     *
     * @return GearmanClient
     */
    public function getClient()
    {
        if (!$this->_client instanceof GearmanClient) {
            $this->_client = new GearmanClient();
            $this->_client->addServer($this->host, $this->port);
            // additional servers
            if (!empty($this->servers)) {
                $this->_client->addServers(implode(',', $this->servers));
            }
        }

        return $this->_client;
    }

    /**
     * Get gearman worker instance
     *
     * @return GearmanWorker
     */
    public function getWorker()
    {
        if (!$this->_worker instanceof GearmanWorker) {
            $this->_worker = new GearmanWorker();
            $this->_worker->addServer($this->host, $this->port);
            // additional servers
            if (!empty($this->servers)) {
                $this->_worker->addServers(implode(',', $this->servers));
            }
        }

        return $this->_worker;
    }

    /**
     * Register new task handler
     *
     * @param string $jobName
     * @param callable $handler
     */
    public function runWorker($jobName, $handler)
    {
        $that = $this;
        $workerHandler = function (\GearmanJob $job) use ($jobName, $handler, $that) {
            // Request before callback
            if (is_callable($that->beforeJobCallback)) {
                call_user_func($that->beforeJobCallback, $jobName, $job);
            }

            // Request worker handler
            $handlerResult = call_user_func($handler, $job);

            // Work finished
            // Request after callback
            if (is_callable($that->afterJobCallback)) {
                call_user_func($that->afterJobCallback, $jobName, $job, $handlerResult);
            }

            return $handlerResult;
        };

        // Get gearman worker instance
        $worker = $this->getWorker();

        // Register gearman worker
        $worker->addFunction($jobName, $workerHandler);

        // Run worker loop
        while ($worker->work()) {
        }
    }

    /**
     * Run a single task
     *
     * @param string $jobName
     * @param array $params
     * @param string $priority low, normal or high
     * @param bool $isBackground
     *
     * @return mixed
     */
    protected function doTask(
        $jobName, Array $params = array(), $priority = self::PRIORITY_NORMAL, $isBackground = false
    ) {
        // Synchronous Gearman functions
        $syncFuncions = array(
            static::PRIORITY_LOW => 'doLow',
            static::PRIORITY_NORMAL => 'doNormal',
            static::PRIORITY_HIGH => 'doHigh',
        );
        // Asynchronous Gearman functions
        $asyncFuncions = array(
            static::PRIORITY_LOW => 'doLowBackground',
            static::PRIORITY_NORMAL => 'doBackground',
            static::PRIORITY_HIGH => 'doHighBackground',
        );

        // Choise functions list
        $functions = $isBackground ? $asyncFuncions : $syncFuncions;

        // Choise function name
        $funcName = $functions[$priority];

        return $this->getClient()->$funcName($jobName, $this->serializeWorkload($params));
    }

    /**
     * Runs a single low priority task
     *
     * @param string $jobName
     * @param array $params
     *
     * @return mixed
     */
    public function doLow($jobName, Array $params = array())
    {
        return $this->doTask($jobName, $params, static::PRIORITY_LOW);
    }

    /**
     * Runs a single task
     *
     * @param string $jobName
     * @param array $params
     *
     * @return mixed
     */
    public function doNormal($jobName, Array $params = array())
    {
        return $this->doTask($jobName, $params, static::PRIORITY_NORMAL);
    }

    /**
     * Runs a single high priority task
     *
     * @param string $jobName
     * @param array $params
     *
     * @return mixed
     */
    public function doHigh($jobName, Array $params = array())
    {
        return $this->doTask($jobName, $params, static::PRIORITY_HIGH);
    }

    /**
     * Runs a low priority task in the background
     *
     * @param string $jobName
     * @param array $params
     *
     * @return mixed
     */
    public function doLowBackground($jobName, Array $params = array())
    {
        return $this->doTask($jobName, $params, static::PRIORITY_LOW, true);
    }

    /**
     * Runs a task in the background
     *
     * @param string $jobName
     * @param array $params
     *
     * @return mixed
     */
    public function doBackground($jobName, Array $params = array())
    {
        return $this->doTask($jobName, $params, static::PRIORITY_NORMAL, true);
    }

    /**
     * Runs a high priority task in the background
     *
     * @param string $jobName
     * @param array $params
     *
     * @return mixed
     */
    public function doHighBackground($jobName, Array $params = array())
    {
        return $this->doTask($jobName, $params, static::PRIORITY_HIGH, true);
    }

    /**
     * Serialize task params
     *
     * @param array|string $params
     *
     * @return string Serialized string
     */
    public function serializeWorkload($params)
    {
        if (!is_array($params)) {
            return (string)$params;
        } else {
            return json_encode($params);
        }
    }

    /**
     * Deserialize task params
     *
     * @param string $workload
     *
     * @return array Deserialized string
     */
    public function deserializeWorkload($workload)
    {
        return json_decode($workload, true);
    }

    /**
     * Return workers array with info
     *
     * example:
     * [
     *     'crop_image' => [
     *         'queued' => 8,
     *         'running' => 8,
     *         'available' => 50,
     *     ],
     *     'save_file' => [
     *         'queued' => 150,
     *         'running' => 10,
     *         'available' => 10,
     *     ],
     * ];
     *
     * @return array
     */
    public function getStatus()
    {
        $command = "(echo status ; sleep 0.1) | netcat $this->host $this->port -w 1";
        $output = shell_exec($command);
        if (empty($output)) {
            return [];
        }

        $workers = [];
        $lines = explode(PHP_EOL, $output);
        foreach ($lines as $line) {
            $matches = [];
            preg_match('/([\w]+)[^\d]+(\d+)\s+(\d+)\s+(\d+)/is', $line, $matches);
            if (!isset($matches[1], $matches[2], $matches[3], $matches[4])) {
                continue;
            }
            list(, $workerName, $queuedJobs, $runningJobs, $availableWorkers) = $matches;

            $workers[$workerName] = [
                'queued' => (int)$queuedJobs,
                'running' => (int)$runningJobs,
                'available' => (int)$availableWorkers,
            ];
        }

        return $workers;
    }

    /**
     * Get free workers count by worker name
     *
     * @param string $workerName 'crop_image'
     *
     * @return int|false Free workers count or FALSE if worker does not running
     */
    public function getFreeWorkersCount($workerName)
    {
        $status = $this->getStatus();

        if (!isset($status[$workerName])) {
            return false;
        }

        $free = $status[$workerName]['available'] - $status[$workerName]['running'];

        return $free > 0 ? $free : 0;
    }
}