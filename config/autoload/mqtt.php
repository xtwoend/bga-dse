<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'default' => [
        'host' => env('MQTT_HOST', 'localhost'),
        'port' => (int) env('MQTT_PORT', 1883),
        'client_id' => env('MQTT_CLIENT_ID', 'hyperf_client_' . uniqid()),
        'clean_session' => (bool) env('MQTT_CLEAN_SESSION', true),
        'username' => env('MQTT_USERNAME', ''),
        'password' => env('MQTT_PASSWORD', ''),
        'keep_alive' => (int) env('MQTT_KEEP_ALIVE', 60),
        'connection_timeout' => (int) env('MQTT_CONNECTION_TIMEOUT', 5),
        'topics' => [
            // BTJM
            'data/bga/btjm/dse/genset1/#' => [
                'qos' => 0,
                'handler' => \App\Mqtt\Handler\BTJM\Genset1TopicHandler::class,
            ],
            'data/bga/btjm/dse/genset2/#' => [
                'qos' => 0,
                'handler' => \App\Mqtt\Handler\BTJM\Genset2TopicHandler::class,
            ],
            'data/bga/btjm/dse/turbine1/#' => [
                'qos' => 0,
                'handler' => \App\Mqtt\Handler\BTJM\Turbine1TopicHandler::class,
            ],
            'data/bga/btjm/dse/turbine2/#' => [
                'qos' => 0,
                'handler' => \App\Mqtt\Handler\BTJM\Turbine2TopicHandler::class,
            ],
            'data/bga/btjm/dse/pln/#' => [
                'qos' => 0,
                'handler' => \App\Mqtt\Handler\BTJM\PLNTopicHandler::class,
            ],
            // BBNM
            'data/bga/bbnm/dse/genset1/#' => [
                'qos' => 0,
                'handler' => \App\Mqtt\Handler\BBNM\Genset1TopicHandler::class,
            ],
            'data/bga/bbnm/dse/genset2/#' => [
                'qos' => 0,
                'handler' => \App\Mqtt\Handler\BBNM\Genset2TopicHandler::class,
            ],
            'data/bga/bbnm/dse/turbine1/#' => [
                'qos' => 0,
                'handler' => \App\Mqtt\Handler\BBNM\Turbine1TopicHandler::class,
            ],
            'data/bga/bbnm/dse/turbine2/#' => [
                'qos' => 0,
                'handler' => \App\Mqtt\Handler\BBNM\Turbine2TopicHandler::class,
            ],
            'data/bga/bbnm/dse/pln/#' => [
                'qos' => 0,
                'handler' => \App\Mqtt\Handler\BBNM\PLNTopicHandler::class,
            ],

            // PNBM
            'data/bga/pnbm/dse/genset1/#' => [
                'qos' => 0,
                'handler' => \App\Mqtt\Handler\PNBM\Genset1TopicHandler::class,
            ],
            'data/bga/pnbm/dse/genset2/#' => [
                'qos' => 0,
                'handler' => \App\Mqtt\Handler\PNBM\Genset2TopicHandler::class,
            ],
            'data/bga/pnbm/dse/turbine1/#' => [
                'qos' => 0,
                'handler' => \App\Mqtt\Handler\PNBM\Turbine1TopicHandler::class,
            ],
            'data/bga/pnbm/dse/turbine2/#' => [
                'qos' => 0,
                'handler' => \App\Mqtt\Handler\PNBM\Turbine2TopicHandler::class,
            ],
            'data/bga/pnbm/dse/pln/#' => [
                'qos' => 0,
                'handler' => \App\Mqtt\Handler\PNBM\PLNTopicHandler::class,
            ],
        ],
    ],
];