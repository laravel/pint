@json(
    $payload
)

@pushIf(
    $wantsScripts,
    'scripts'
)
    <script></script>
@endPushIf
