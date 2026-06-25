<div
    x-data="{
    open: false,
    count: 0,
    toggle() { this.open = ! this.open },
    }"
    x-init="count = 1"
    x-show="open"
    x-on:click.outside="open = false"
    @keydown.escape.window="open = false"
    :class="{ 'hidden': ! open, 'block': open }"
>
    <button x-on:click="toggle()" :aria-expanded="open" x-text="open ? 'Close' : 'Open'"></button>
    <span x-show="! loading && count > 0">Ready</span>
    <input x-model="search" :placeholder="!focused ? 'Search...' : ''" />
</div>
