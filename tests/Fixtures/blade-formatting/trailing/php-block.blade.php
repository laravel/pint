@php
    $data = [
        'one' => 1,
        'two' => 2,
    ];
@endphp

@php($inline = ['a' => 1])

<p>{{    $data['one']    }}</p>
