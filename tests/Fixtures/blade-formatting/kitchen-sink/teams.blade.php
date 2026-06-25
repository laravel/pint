<?php

use App\Models\Team;
use Livewire\Volt\Component;
use Illuminate\Support\Collection;

new class extends Component
{
    public string $name = "";

    public function teams(): Collection
    {
        return Team::query()->where("user_id", auth()->id())->get();
    }

    public function with(): array
    {
        return [
            "teams" => $this->teams(),
        ];
    }
}; ?>

<div class="space-y-6 p-6 max-w-2xl mx-auto">
<flux:heading size="xl">{{__('Teams')}}</flux:heading>

@props([
        'showCreate' => true,
    ])

@if(   $teams->isEmpty()   )
<flux:callout>{{__('You have no teams yet.')}}</flux:callout>
@else
<ul class="divide-y">
@foreach(   $teams as $team   )
<li class="py-2 flex items-center justify-between" wire:key="team-{{$team->id}}">
<span class="{{$team->is_active ? 'font-bold' : ''}}">{{$team->name}}</span>
<flux:button :variant="$team->is_active ? 'primary' : 'ghost'" wire:click="select({{$team->id}})" @click="open = true">
{{__('Select')}}
</flux:button>
</li>
@endforeach
</ul>
@endif

@include('partials.footer', [
        'year' => now()->year,
        'links' => ['about', 'contact'],
    ])
</div>
