<div>
    <?php $greeting = 'Hello'; ?>
        <p>{{$greeting}}</p>

@php
        $status = match ($state) {
            'active' => 'On',
            'paused' => 'Paused',
            default => 'Unknown',
        };

        $handler = function (int $value): int {
            return $value * 2;
        };

        $tags = ['php', 'blade', 'laravel'];
    @endphp

    <span>{{   $status   }}</span>
    <ul>
@foreach    ($tags as $tag)
            <li>{{ $handler(strlen($tag)) }}</li>
        @endforeach
    </ul>
</div>
