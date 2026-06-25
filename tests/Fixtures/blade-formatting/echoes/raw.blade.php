<div>
    <div>{!!$markdown!!}</div>
    <div>{!! $html !!}</div>
    <p>{{$escaped}} and {!!$raw!!}</p>
    <article>{!! Str::markdown($post->body) !!}</article>
    {!! $svg !!}
</div>
