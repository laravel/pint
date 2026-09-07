<?php

use App\Models\Category;

$children = Category::all();
$parents = \App\Models\Category::roots();
$mode = \App\Enums\CalculationMode::Default;
?>

<div>{{ count($children) + count($parents) }} {{ $mode->value }}</div>
