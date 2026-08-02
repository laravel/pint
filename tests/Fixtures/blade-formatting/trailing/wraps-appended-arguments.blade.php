@extends(
    'layouts.app',
    ['title' => 'Dashboard']
)

@include(
    'partials.card',
    ['title' => 'Hello']
)

@includeIf(
    'partials.card',
    ['title' => 'Hello']
)

@includeWhen(
    $showCard,
    'partials.card',
    ['title' => 'Hello']
)

@includeUnless(
    $hideCard,
    'partials.card',
    ['title' => 'Hello']
)

@includeFirst(
    ['partials.card', 'partials.fallback'],
    ['title' => 'Hello']
)
