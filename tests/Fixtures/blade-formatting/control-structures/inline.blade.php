<div>
    @isset($user)
        <p>{{ $user->name }}</p>
    @endisset
    @empty($items)
        <p>No items</p>
    @endempty
    @hasSection('sidebar')
        <aside>@yield('sidebar')</aside>
    @endif
    <p>@if ($admin) Admin @else Guest @endif</p>
    @unless(Auth::check())
        <a href="/login">Login</a>
    @endunless
    @production
        <span>live</span>
    @endproduction
    @env('local')
        <span>local</span>
    @endenv
</div>
