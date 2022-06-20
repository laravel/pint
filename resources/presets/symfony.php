<?php

use App\Factories\ConfigurationFactory;

return ConfigurationFactory::preset([
    '@Symfony' => true,
    'no_unused_imports' => true,
]);
