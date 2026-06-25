<?php

use Illuminate\View\Component;
use App\Models\User;
use Livewire\Volt\Component as VoltComponent;

new class extends Component
{
    public array $items = [
        "first",
        "second",
    ];

    public function with(): array
    {
        return [
            "count" => count($this->items),
        ];
    }
}; ?>

<div>
    @foreach($items as $item)
        <p>{{ $item }}</p>
    @endforeach
</div>
