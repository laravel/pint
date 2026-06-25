<div x-data="{ open: false }" x-init="open = true" x-show="open" x-cloak>
        <flux:button :variant="$variant" wire:model="name" x-on:click="open = !open" @click="toggle()">
        Go
    </flux:button>
  <x-slot name="header" class="font-bold">
        Title
        </x-slot>
        <flux:icon name="check" />
  <livewire:user-profile :user="$user" />
</div>
