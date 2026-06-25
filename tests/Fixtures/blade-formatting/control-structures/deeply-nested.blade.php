<x-app-layout>
        <x-slot:header>
<flux:heading>Teams</flux:heading>
  </x-slot:header>
<div>
@if (  $teams->isNotEmpty()  )
        <ul>
@foreach (  $teams as $team  )
  <li>
@can('view', $team)
        <a href="{{route('teams.show', $team)}}">
@if (  $team->is_current  )
<strong>{{$team->name}}</strong>
@else
{{    $team->name    }}
@endif
</a>
@endcan
</li>
@endforeach
</ul>
@endif
</div>
</x-app-layout>
