<head>
    @stack('styles')
    @push('styles')
        <link rel="stylesheet" href="/app.css" />
    @endpush
    @prepend('scripts')
        <script src="/early.js"></script>
    @endprepend
    @once
        @push('scripts')
            <script src="/once.js"></script>
        @endpush
    @endonce
    @pushOnce('meta')
        <meta name="csrf" content="{{ csrf_token() }}" />
    @endPushOnce
</head>
