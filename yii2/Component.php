<?php

namespace demi\gearman\yii2;

use demi\gearman\GearmanQueue;
use demi\gearman\SupervisorConfig;

/**
 * Queue base class
 *
 * @property GearmanQueue $queue
 *
 * Magic methods
 * @method mixed runWorker() runWorker(string $jobName, callable $handler) Register new task handler
 * @method mixed getStatus() getStatus() Return workers array with info
 * @method mixed getFreeWorkersCount() getFreeWorkersCount(string $workerName) Get free workers count by worker name
 *
 * @method mixed doLow() doLow(string $taskName, Array $params = []) Runs a single low priority task
 * @method mixed doNormal() doNormal(string $taskName, Array $params = []) Runs a single task
 * @method mixed doHigh() doHigh(string $taskName, Array $params = []) Runs a single high priority task
 *
 * @method mixed doLowBackground() doLowBackground(string $taskName, Array $params = []) Runs a low priority task in the background
 * @method mixed doBackground() doBackground(string $taskName, Array $params = []) Runs a task in the background
 * @method mixed doHighBackground() doHighBackground(string $taskName, Array $params = []) Runs a high priority task in the background
 *
 * @method mixed serializeWorkload() serializeWorkload(Array $params = []) Serialize task params
 * @method mixed deserializeWorkload() deserializeWorkload(string $workload = []) Deserialize task params
 */
class Component extends \yii\base\Component
{
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
    public $servers = [];
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
     * Supervisor config:
     *
     * [
     *     'configFile' => '/etc/supervisor/conf.d/workers.conf',
     *     'workersDirectory' => '/var/www/site',
     *     'restartSleepingTime' => 5,
     *     'all' => [
     *         'crop_image' => ['numprocs' => 0, 'command' => '/usr/bin/php yii workers/crop-image'],
     *         'bad_worker' => ['numprocs' => 0, 'command' => '/usr/bin/php yii workers/bad-worker'],
     *     ],
     *     'sets' => [
     *         'general' => [
     *             'crop_image' => 5,
     *         ],
     *         'minimal' => [
     *             'crop_image' => 50,
     *             'bad_worker' => 50,
     *         ],
     *         'maximal' => [
     *             'crop_image' => 100,
     *             'bad_worker' => 100,
     *         ],
     *     ],
     * ]
     *
     * @var array
     */
    public $supervisorConfig = [];

    /**
     * Gearman queue component instance
     *
     * @var GearmanQueue
     */
    protected $_queue;

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Initialize queue component
        $queue = new GearmanQueue($this->host, $this->port, $this->servers);
        $queue->beforeJobCallback = $this->beforeJobCallback;
        $queue->afterJobCallback = $this->afterJobCallback;

        $this->_queue = $queue;

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $params)
    {
        $available = [
            'runWorker', 'getStatus', 'getFreeWorkersCount',
        ];
        if (in_array($name, $available) || substr($name, 0, 2) === 'do' || strpos($name, 'serialize') !== false) {
            return call_user_func_array([$this->queue, $name], $params);
        }

        return parent::__call($name, $params);
    }

    /**
     * Get queue component instance
     *
     * @return GearmanQueue
     */
    public function getQueue()
    {
        return $this->_queue;
    }

    /**
     * Set queue component instance
     *
     * @param GearmanQueue $queue
     */
    public function setQueue(GearmanQueue $queue)
    {
        $this->_queue = $queue;
    }

    /**
     * Run supervisor configurator
     */
    public function configureSupervisor()
    {
        $config = $this->supervisorConfig;
        $supervisor = new SupervisorConfig($config['configFile'], $config['workersDirectory']);
        $supervisor->restartSleepingTime = $config['restartSleepingTime'];
        $supervisor->workersConfig = $config['all'];
        $supervisor->workersSets = $config['sets'];

        $supervisor->requestUpdate();
    }
}