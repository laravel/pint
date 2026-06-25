<div>
    @@if ($condition)
        This @@if is rendered literally, not parsed.
    @@endif

   <p>@{{ vueVariable }}</p>
        <span>@{{ user.name }}</span>
   <div v-bind:class="@{{ classes }}"></div>

    @verbatim
        <div x-data="{ count: 0 }">
            <span x-text="count"></span>
            {{ thisIsLiteral }}
        </div>
    @endverbatim

   <p>Real echo: {{   $value   }}</p>
        @{{-- this is a literal, not a blade comment --}}
</div>
