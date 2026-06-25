<span @class(['badge', 'badge-active' => $isActive])>
    {{ $slot }}
</span>

<button type="submit" @disabled($processing)>
    {{ $label }}
</button>

@verbatim
<span> {{ vueVar }} </span>
@endverbatim
