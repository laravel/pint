<?php

use \App\Models\Category;
use \App\Enums\CalculationMode as Mode;

$rows = Category::all();
?>

<div class="grid grid-cols-{{ Mode::count() }}">{{ count($rows) }}</div>
