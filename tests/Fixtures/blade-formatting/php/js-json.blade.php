<div x-data="follow({{ $id }}, @js($isFollowing), @js(auth()->check()))">
    @php
        $total = collect($items)->sum('price');
        $label = $total > 100 ? 'expensive' : 'cheap';
    @endphp
    @php($formatted = number_format($total, 2))
    <span data-config="@json(['count' => $count, 'name' => $name])"></span>
    <p>{{ $label }}: {{ $formatted }}</p>
    <script>
        const data = @json($payload);
    </script>
</div>
