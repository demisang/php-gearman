<?php
/**
 * @copyright Copyright (c) 2018 Ivan Orlov
 * @license   https://github.com/demisang/php-gearman/blob/master/LICENSE
 * @link      https://github.com/demisang/php-gearman#readme
 * @author    Ivan Orlov <gnasimed@gmail.com>
 */

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
