{{-- format-ignore-start --}}
@foreach($messageData as $ecrewMessage)
Dear {{$ecrewMessage->name}}
@endforeach
{{-- format-ignore-end --}}
<script>
    {{-- prettier-ignore-start --}}
    const  matrix = [
        1,  2,  3,
    ];
    {{-- prettier-ignore-end --}}
</script>
<div x-show="!visible" {{-- format-ignore-start --}} id = "preserved">
    @php    $value = !$hidden; @endphp
</div>
{{-- format-ignore-end --}}
<div  id="after"></div>
