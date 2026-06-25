<form>
    @csrf
    @if($ok)
        ok
    @else
        no
    @endif
</form>
@foreach($items as $item)
    {{ $item }}
@endforeach
@auth
    in
@endauth
@guest
    out
@endguest
@once
    once
@endonce
@php
    $x = 1;
@endphp
