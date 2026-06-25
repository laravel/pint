<div x-data="{ count: 0 }">
    <span>@{{ count }}</span>
    <p>@{{ user.name }} said @{{ message }}</p>
    @{{-- this is not a blade comment --}}
    <button @@click="count++">@@isset is escaped</button>
    <code>@@if ($x) ... @@endif</code>
    <p>{{ $real }} next to @{{ vueValue }}</p>
</div>
