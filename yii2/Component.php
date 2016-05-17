<?php

namespace demi\gearman\yii2;

use yii\helpers\Json;
use demi\gearman\GearmanQueue;


/**
 * Queue base class
 *
 * @property GearmanQueue $queue
 *
 * Magic methods
 * @method mixed doLow() doLow(string $taskName, Array $params = []) Runs a single low priority task
 * @method mixed doNormal() doNormal(string $taskName, Array $params = []) Runs a single task
 * @method mixed doHigh() doHigh(string $taskName, Array $params = []) Runs a single high priority task
 * @method mixed doLowBackground() doLowBackground(string $taskName, Array $params = []) Runs a low priority task in the background
 * @method mixed doBackground() doBackground(string $taskName, Array $params = []) Runs a task in the background
 * @method mixed doHighBackground() doHighBackground(string $taskName, Array $params = []) Runs a high priority task in the background
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
        if (substr($name, 0, 2) === 'do' || strpos($name, 'serialize') !== false) {
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
}