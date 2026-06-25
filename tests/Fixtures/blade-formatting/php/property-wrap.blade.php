    <script>
    window.APP_ENUMS = @js([
        'sizes' => [
            \App\Enums\ServerType::DEDICATED_HIGH_MEMORY->value => \App\Enums\ServerType::DEDICATED_HIGH_MEMORY->availableSizes(),
        ],
    ]);
</script>
