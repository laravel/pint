<?php

return [
    'cache' => false,

    'compiled' => realpath(sys_get_temp_dir()),

    'paths' => [
        resource_path('views'),
    ],
];
