<?php

return [
    'host' => env('GEARMAN_HOST', '127.0.0.1'),
    'port' =>  env('GEARMAN_PORT', 4730),
    'supervisorConfig' => [
        'configFile' => '/etc/supervisor/conf.d/workers.conf',
        'workersDirectory' => realpath(__DIR__ . '/../'),
        'restartSleepingTime' => 5,
        'commandConfigs' => [
            [
                'id' => 'some_work_id',
                'name' => 'worker:some-name',
            ]

        ]
    ],
];
