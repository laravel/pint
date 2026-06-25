@if($x > $y)
    a
@elseif($x < $y)
    b
@else
    c
@endif

@unless($ok)
    d
@endunless

@while($i < 10)
    e
@endwhile

@for($i = 0; $i < 3; $i++)
    f
@endfor

@foreach($items as $item)
    {{ $item }}
@endforeach

@forelse($users as $user)
    {{ $user }}
@empty
    none
@endforelse

@switch($type)
    @case(1)
        one
        @break
    @default
        other
@endswitch
