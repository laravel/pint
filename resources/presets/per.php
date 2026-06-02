<?php

use App\Factories\ConfigurationFactory;

return ConfigurationFactory::preset([
    '@PER-CS' => true,
    'no_unused_imports' => true,
]);
