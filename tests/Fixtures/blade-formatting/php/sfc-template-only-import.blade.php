<?php

use App\Enums\CalculationMode;

$default = "all";
?>

<div>
    <span>{{ CalculationMode::count() }}</span>
    <span>{{ $default }}</span>
</div>
