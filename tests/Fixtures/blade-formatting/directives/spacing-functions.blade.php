@extends(  'layouts.app'  )
@section(   'title', 'Home'   )
@yield(  'content'  )
@include(   'partials.nav'   )
@includeIf(  'partials.aside'  )
@includeWhen(  $cond, 'partials.x'  )
@includeUnless(  $cond, 'partials.y'  )
@each(   'view.name', $jobs, 'job'   )
@props(  ['a', 'b']  )
@vite(  'resources/js/app.js'  )
@error(   'email'   )
{{   $message   }}
@enderror
@persist(  'cart'  )
x
@endpersist
@can(  'update', $post  )
can
@endcan
@cannot(   'update', $post   )
cannot
@endcannot
@canany(  ['update', 'delete'], $post  )
any
@endcanany
@method(  'PUT'  )
@json(   ['x' => 1]   )
@class(  ['p-4', 'font-bold' => $active]  )
@style(  ['color: red']  )
<input @checked(  $on  ) @selected( $sel ) @disabled(  $dis  ) @readonly( $ro ) @required(  $req  ) />
@lang(   'messages.welcome'   )
@push(  'scripts'  )
x
@endpush
@pushOnce(   'styles'   )
y
@endPushOnce
@prepend(  'head'  )
z
@endprepend
@use(  'App\Models\User'  )
@inject(   'metrics', 'App\Services\Metrics'   )
