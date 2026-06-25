<div>
<p>{{    $user->profile?->name ?? 'Guest'    }}</p>
      <p>{{ $active ? 'Yes' : 'No' }}</p>
   <p>{{ $items->map(fn ($i) => $i->name)->implode(', ') }}</p>
        <p>{{$data['nested']['key']}}</p>
<p>{{ strtoupper($title) . ' — ' . $subtitle }}</p>
<p>{{number_format($total, 2)}}</p>
   <p>{!!     $html ?: '<em>empty</em>'     !!}</p>
<p>{{ count($items) > 0 ? "{$count} found" : 'none' }}</p>
        <p>{{ $loop->iteration }}/{{ $loop->count }}</p>
<a href="{{ route('users.show', ['user' => $user, 'tab' => 'profile']) }}">View</a>
   <p>{{ $price > 100 ? __('Premium') : __('Standard') }}</p>
</div>
