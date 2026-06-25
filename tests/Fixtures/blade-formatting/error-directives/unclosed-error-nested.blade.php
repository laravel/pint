@extends(  'layouts.default'  )

@section(   'content'   )
<section>
<div>
@can(  'reply', $thread  )
@if(  $thread->isUnlocked()  )
<div class="my-8">
<form action="{{ route('replies.store') }}" method="POST">
@csrf

@error(  'body'  )

<input type="hidden" name="replyable_id" value="{{ $thread->id() }}" />
</form>
</div>
@else
<p>The conversation is old.</p>
@endif
@else
@guest
<p><a href="{{ route('login') }}">Sign in</a> to participate.</p>
@else
<div>
<p>You'll need to verify your account.</p>
</div>
@endguest
@endcan
</div>
</section>
@endsection
