<?php

namespace demi\gearman\yii2;

use yii\console\Controller;

/**
 * Supervisor config action
 */
class SupervisorController extends Controller
{
    public $gearmanComponentName = 'gearman';

    /**
     * Request user supervisor config set
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function actionIndex()
    {
        /** @var \demi\gearman\yii2\Component $component */
        $component = \Yii::$app->get($this->gearmanComponentName);

        $component->configureSupervisor();
    }
}