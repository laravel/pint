<?php

use App\Factories\ConfigurationFactory;

return ConfigurationFactory::preset([
    '@PER' => true,
    'no_unused_imports' => true,
]);
