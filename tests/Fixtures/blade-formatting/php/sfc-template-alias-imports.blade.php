<?php

use function App\Support\money;

use const App\Support\CURRENCY;

use App\Enums\CalculationMode as Mode;
use App\Models\User;

$total = "0";
?>

<div>
<span>{{ money($total, CURRENCY) }}</span>
@if(Mode::count() > 1)
        <span>{{ Mode::default()->label() }}</span>
    @endif
</div>
