@extends('layouts.app')

@section('title', 'Dashboard')

@section('sidebar')
  @parent
        <p>This is appended to the master sidebar.</p>
@endsection

@section('content')
        <h1>Dashboard</h1>
  @hasSection('subtitle')
        <h2>@yield('subtitle')</h2>
    @endif
        @sectionMissing('banner')
        <div class="default-banner">Welcome</div>
    @endif
  <p>@yield('greeting', 'Hello there')</p>
@stop

@section('meta')
        <meta name="description" content="x" />
@append

@section('legacy')
  <span>old</span>
@overwrite

@section('scripts')
        <script src="/app.js"></script>
@show
