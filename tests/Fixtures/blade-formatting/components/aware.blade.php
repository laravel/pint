<div>
    @aware(['color' => 'gray'])
    @props([
        'variant' => 'primary',
        'size' => 'md',
    ])

    <button {{ $attributes->merge(['class' => "btn btn-{$color}"]) }}>
        {{ $slot }}
    </button>
</div>
