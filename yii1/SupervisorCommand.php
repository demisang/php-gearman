<?php

namespace demi\gearman\yii1;

/**
 * Supervisor config action
 */
class SupervisorCommand extends \CConsoleCommand
{
    public $gearmanComponentName = 'gearman';

    /**
     * Request user supervisor config set
     */
    public function actionIndex()
    {
        /** @var \demi\gearman\yii1\GearmanComponent $component */
        $component = \Yii::app()->getComponent($this->gearmanComponentName);

        $component->configureSupervisor();
    }
}