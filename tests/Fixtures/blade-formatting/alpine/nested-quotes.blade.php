<div
    x-data="{ open: false, label: 'Say \'hi\'', items: [1, 2, 3], greet() { return `Hello, ${this.label}` } }"
    x-init="$watch('open', value => console.log(value))"
>
    <button @click="open = !open" :class="{ 'is-open': open, 'text-red': hasError }" x-bind:aria-expanded="open.toString()">
        Toggle
    </button>
    <div x-show="open" x-transition.duration.300ms x-cloak>
        <span x-text="items.join(', ')"></span>
    </div>
</div>
