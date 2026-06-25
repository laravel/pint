<div>
    {{--
        A multi-line blade comment
        spanning several lines.
    --}}
    <p>Visible</p>
    {{-- @if ($x) commented-out directive @endif --}}
    {{-- <flux:button>commented component</flux:button> --}}
    <span>{{ $value }}</span>{{-- trailing comment --}}
    {{--
        @foreach ($items as $item)
            {{ $item }}
        @endforeach
    --}}
    <button @class(['btn']) {{-- inline note --}}>Go</button>
</div>
