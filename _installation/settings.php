<?php
$__GRAPHENE_SETTINGS = [
    "debug" => true,
    "stats" => false,
    "baseUrl" => "",
    "frameworkDir" => "",
    "modulesUrl" => "modules",
    "appName" => "Graphene",
    "logsDir" => "logs",
    "storageConfig" => [
        "host" => "localhost",
        "driver" => "Graphene\db\drivers\mysql\MysqlDriver",
        "type" => "mysql",
        "dbName" => "nemesis_db",
        "prefix" => "gdb_",
        "username" => "root",
        "password" => "mysql"
    ],

    'logging' => [
        'appenders' => [
            'default' => [
                'class' => 'LoggerAppenderFile',
                'layout' => [
                    'class' => 'LoggerLayoutPattern',
                    'params' => [
                        'conversionPattern' => '%date %-5level  %msg   %logger [%C:%L] %n'
                    ]
                ],
                'params' => [
                    'file' => __DIR__ . '/logs/graphene.log',
                    'append' => true,
                ]
            ]
        ],
        'rootLogger' => [
            'appenders' => ['default']
        ],
    ]
];