<?php

return [
    'managers' => [
        'default' => [
            'dev'        => env
('APP_DEBUG', false),
            'meta'       => 'attributes',
            'connection' => env('db_CONNECTION', 'pgsql'),
            'paths'      => [ base_path('app/Domain/Entities') ],
            'proxies'    => [
                'namespace' => 'Proxies',
                'path'      => storage_path('proxies'),
                'auto_generate' => env('DOCTRINE_PROXY_AUTOGENERATE', true),
            ],
        ],
    ],

    'dbal' => [
        'types' => [
            'geometry'            => LongitudeOne\Spatial\DBAL\Types\GeometryType::class,
            'geometry_point'      => LongitudeOne\Spatial\DBAL\Types\Geometry\PointType::class,
            'geometry_polygon'    => LongitudeOne\Spatial\DBAL\Types\Geometry\PolygonType::class,
            'geometry_linestring' => LongitudeOne\Spatial\DBAL\Types\Geometry\LineStringType::class,
        ],
    ],

    'connections' => [
        'pgsql' => [
            'driver'   => 'pdo_pgsql',
            'host'     => env('DB_HOST', 'db'),
            'dbname'   => env('DB_DATABASE', 'app'),
            'user'     => env('DB_USERNAME', 'app'),
            'password' => env('DB_PASSWORD', 'secret'),
            'port'     => (int) env('DB_PORT', 5432),
            'charset'  => 'utf8',
        ],
     ],
];
