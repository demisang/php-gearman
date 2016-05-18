php-gearman-job-server
===================

Gearman job server workers helper

Installation
------------
Run
```code
composer require "demi/php-gearman" "~1.0"
```

Configuration
-------------
### Yii1/Yii2/Laravel:
supervisor.php at you common config dir:
```php
return [
    'configFile' => '/etc/supervisor/conf.d/workers.conf',
    'workersDirectory' => '/var/www/site',
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
common/config/main.php:
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
console/config/main.php:
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
protected/config/main.php:
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
protected/config/console.php:
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
coming soon...


Usage
-----
#### Change supervisor config set and restart supervisor:
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

