<?php

use App\Factories\ConfigurationFactory;

return ConfigurationFactory::preset([
    '@PSR2' => true,
    'no_unused_imports' => true,
]);
