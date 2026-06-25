<ul>
@foreach (  $users as $user  )
        @continue($user->isBanned())
  @break($loop->iteration > 10)
        <li class="{{$loop->first ? 'first' : ''}} {{    $loop->last ? 'last' : ''    }}">
            {{$loop->iteration}}. {{ $user->name }}
  @foreach (  $user->posts as $post  )
        @continue
            @if (  $post->isDraft()  )
        @break
                @endif
        <span>{{$loop->parent->index}}-{{$loop->index}}</span>
            @endforeach
        </li>
    @endforeach
</ul>
