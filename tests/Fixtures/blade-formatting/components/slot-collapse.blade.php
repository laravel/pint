<x-app-layout>
    <x-slot name="title">Bookmarks</x-slot>
    <x-slot:heading>Dashboard</x-slot:heading>
    <x-slot name="count">{{ $count }} items</x-slot>
    <x-slot name="body">
        Some text with <a href="#">a link</a> stays expanded
    </x-slot>
    <x-slot name="directive">
        @if ($x) yes @endif
    </x-slot>
</x-app-layout>
