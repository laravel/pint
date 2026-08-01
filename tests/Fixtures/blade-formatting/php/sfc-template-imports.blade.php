<?php

use App\Enums\CalculationMode;
use App\Models\User;
use Illuminate\View\Component;

new class extends Component
{
    public string $mode = "all";
};
?>

<div class="grid grid-cols-{{ CalculationMode::count() }}">
@foreach(CalculationMode::cases() as $case)
        <span>{{ $case->label() }}</span>
    @endforeach
</div>
