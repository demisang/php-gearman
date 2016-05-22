php-gearman-job-server
===================

Gearman job server workers helper

Installation
------------
* Run:
```code
composer require "demi/php-gearman" "~1.0"
```
* Install gearman job server as PHP-extension: http://gearman.org/getting-started/#gearman_php_extension<br />
* Install supervisor:
```bash
apt-get install supervisor
```
* (optional) Install Gearman GUI: http://gaspaio.github.io/gearmanui


Configuration
-------------
### Yii1/Yii2:
supervisor.php at you common config dir:
```php
return [
    'configFile' => '/etc/supervisor/conf.d/workers.conf',
    'workersDirectory' => realpath(__DIR__ . '/../'),
    'restartSleepingTime' => 5,
    'all' => [
        'crop_image' => ['numprocs' => 0, 'command' => '/usr/bin/php yii workers/crop-image'],
        'bad_worker' => ['numprocs' => 0, 'command' => '/usr/bin/php yii workers/bad-worker'],
    ],
    'sets' => [
        'general' => [
            'crop_image' => 5,
        ],
        'minimal' => [
            'crop_image' => 50,
            'bad_worker' => 50,
        ],
        'maximal' => [
            'crop_image' => 100,
            'bad_worker' => 100,
        ],
    ],
];
```

## Gearman component configuration

### Yii2
/common/config/main.php:
```php
'components' => [
    'gearman' => [
        'class' => '\demi\gearman\yii2\Component',
        'host' => '127.0.0.1',
        'port' => 4730,
        'supervisorConfig' => require(__DIR__ . '/supervisor.php'),
    ],
];
```
/console/config/main.php:
```php
return [
    'controllerMap' => [
        'gearman' => [
            'class' => '\demi\gearman\yii2\SupervisorController',
            'gearmanComponentName' => 'gearman', // name of component: Yii::$app->gearman (from previous config listing)
        ],
    ],
],
```


### Yii1
/protected/config/main.php:
```php
'components' => [
    'gearman' => [
        'class' => '\demi\gearman\yii1\GearmanComponent',
        'host' => '127.0.0.1',
        'port' => 4730,
        'supervisorConfig' => require(__DIR__ . '/supervisor.php'),
    ],
];
```
/protected/config/console.php:
```php
return [
    'commandMap' => [
        'gearman' => [
            'class' => '\demi\gearman\yii1\SupervisorCommand',
            'gearmanComponentName' => 'gearman', // name of component: Yii::app()->gearman (from previous config listing)
        ],
    ],
],
```

### Laravel:
Publish /config/gearman.php
```bash
php artisan vendor:publish --provider="demi\gearman\laravel5\GearmanServiceProvider" --tag=config
```

Add service provider to /config/app.php:
```php
'providers' => [
    // Gearman helper
    demi\gearman\laravel5\GearmanServiceProvider::class
],
'aliases' => [
    // Gearman helper
    'Gearman' => demi\gearman\laravel5\GearmanFacade::class,
],
```


Usage
-----
### Running workers:
Gearman workers - it is simple looped console commands

##### Yii2:
Create new console controller<br />
/console/controllers/WorkersController.php:
```php
<?php

namespace console\controllers;

use Yii;
use GearmanJob;

/**
 * Gearman workers
 */
class WorkersController extends \yii\console\Controller
{
    /**
     * Crop image worker
     */
    public function actionCropImage()
    {
        Yii::$app->gearman->runWorker('crop_image', function (GearmanJob $job) {
            $workload = Yii::$app->gearman->deserializeWorkload($job->workload());
            $imagePath = $workload['image_path'];
            if (empty($imagePath)) {
                return Yii::$app->gearman->serializeWorkload(['status' => 'error', 'message' => 'No image']);
            }

            // Do some job...

            return Yii::$app->gearman->serializeWorkload(['status' => 'success', 'foo' => 'bar']);
        });
    }
}
````

##### Yii1:
Create new console command<br />
/protected/commands/WorkersCommand.php:
```php
<?php

/**
 * Gearman workers
 */
class WorkersCommand extends CConsoleCommand
{
    /**
     * Crop image worker
     */
    public function actionCropImage()
    {
        Yii::app()->gearman->runWorker('crop_image', function (GearmanJob $job) {
            $workload = Yii::app()->gearman->deserializeWorkload($job->workload());
            $imagePath = $workload['image_path'];
            if (empty($imagePath)) {
                return Yii::app()->gearman->serializeWorkload(['status' => 'error', 'message' => 'No image']);
            }

            // Do some job...

            return Yii::app()->gearman->serializeWorkload(['status' => 'success', 'foo' => 'bar']);
        });
    }
}
````

##### Laravel:
Create new console command<br />
/app/Console/Commands/CropImage.php:
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GearmanJob;
use Gearman;

/**
 * Gearman crop image worker
 */
class CropImage extends Command
{
    /**
     * @inheritdoc
     */
    protected $name = 'worker:crop-image';

    /**
     * @inheritdoc
     */
    protected $description = 'Worker for cropping image';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Gearman::runWorker('crop_image', function (GearmanJob $job) {
            $workload = Gearman::deserializeWorkload($job->workload());
            $imagePath = $workload['image_path'];
            if (empty($imagePath)) {
                return Gearman::serializeWorkload(['status' => 'error', 'message' => 'No image']);
            }

            // Do some job...

            return Gearman::serializeWorkload(['status' => 'success', 'foo' => 'bar']);
        });
    }
}
````
Update /app/Console/Kernel.php:<br />
Add to `protected $commands`:
```php
protected $commands = [
    // ...
    \App\Console\Commands\CropImage::class,
]
```


### Change supervisor config set and restart supervisor:
Yii2:
```bash
php yii gearman
````

Yii1:
```bash
php yiic gearman
````

Laravel:
```bash
php artisan gearman
````


Examples
--------
##### Yii2:
At any place:
```php
// synchronous
$result = Yii::$app->gearman->doNormal('crop_image', ['image_path' => '/var/www/image.jpg']);
var_dump(Yii::$app->gearman->deserializeWorkload($result)); // ['status' => 'success', 'foo' => 'bar']
$result = Yii::$app->gearman->doNormal('crop_image');
var_dump(Yii::$app->gearman->deserializeWorkload($result)); // ['status' => 'error', 'message' => 'No image']

// asynchronous
$result = Yii::$app->gearman->doBackground('crop_image', ['image_path' => '/var/www/image.jpg']);
var_dump($result); // job handle file descriptior
$result = Yii::$app->gearman->doBackground('crop_image');
var_dump($result); // job handle file descriptior

// Variants:
// doLow(), doNormal(), doHigh(),
// doLowBackground(), doBackground(), doHighBackground(),
```

##### Yii1:
At any place:
```php
// synchronous
$result = Yii::app()->gearman->doNormal('crop_image', ['image_path' => '/var/www/image.jpg']);
var_dump(Yii::app()->gearman->deserializeWorkload($result)); // ['status' => 'success', 'foo' => 'bar']
$result = Yii::app()->gearman->doNormal('crop_image');
var_dump(Yii::app()->gearman->deserializeWorkload($result)); // ['status' => 'error', 'message' => 'No image']

// asynchronous
$result = Yii::app()->gearman->doBackground('crop_image', ['image_path' => '/var/www/image.jpg']);
var_dump($result); // job handle file descriptior
$result = Yii::app()->gearman->doBackground('crop_image');
var_dump($result); // job handle file descriptior

// Variants:
// doLow(), doNormal(), doHigh(),
// doLowBackground(), doBackground(), doHighBackground(),
```

##### Laravel:
At any place:
```php
use Gearman;

// synchronous
$result = Gearman::doNormal('crop_image', ['image_path' => '/var/www/image.jpg']);
var_dump(Gearman::deserializeWorkload($result)); // ['status' => 'success', 'foo' => 'bar']
$result = Gearman::doNormal('crop_image');
var_dump(Gearman::deserializeWorkload($result)); // ['status' => 'error', 'message' => 'No image']

// asynchronous
$result = Gearman::doBackground('crop_image', ['image_path' => '/var/www/image.jpg']);
var_dump($result); // job handle file descriptior
$result = Gearman::doBackground('crop_image');
var_dump($result); // job handle file descriptior

// Variants:
// doLow(), doNormal(), doHigh(),
// doLowBackground(), doBackground(), doHighBackground(),