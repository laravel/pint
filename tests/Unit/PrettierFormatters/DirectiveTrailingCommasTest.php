<?php

use App\PrettierFormatters\DirectiveTrailingCommas;

it('adds a trailing comma to a multiline hugged array', function () {
    $in = "@props([\n    'sidebar' => false\n])\n";
    $out = "@props([\n    'sidebar' => false,\n])\n";

    expect((new DirectiveTrailingCommas)->postFormat($in))->toBe($out);
});

it('does not add a comma to a single-line array', function () {
    $in = "@props(['a', 'b'])\n";

    expect((new DirectiveTrailingCommas)->postFormat($in))->toBe($in);
});

it('adds a comma to a multiline array inside a multi-argument directive', function () {
    $in = "@include('view',\n    [\n        'foo' => 1,\n        'bar' => 2\n    ])\n";
    $out = "@include('view',\n    [\n        'foo' => 1,\n        'bar' => 2,\n    ])\n";

    expect((new DirectiveTrailingCommas)->postFormat($in))->toBe($out);
});

it('adds a comma to a wrapped call argument list', function () {
    $in = "@can(\n    'update',\n    \$user,\n    \$post\n)\n";
    $out = "@can(\n    'update',\n    \$user,\n    \$post,\n)\n";

    expect((new DirectiveTrailingCommas)->postFormat($in))->toBe($out);
});

it('never adds a comma to a control-structure condition', function () {
    $in = "@if (\n    \$a &&\n    \$b\n)\n@endif\n";

    expect((new DirectiveTrailingCommas)->postFormat($in))->toBe($in);
});

it('never adds a comma to a directive that is not a call', function (string $directive, string $expression) {
    $in = "@{$directive}(\n    {$expression}\n)\n";

    expect((new DirectiveTrailingCommas)->postFormat($in))->toBe($in);
})->with([
    // Compiled to "if (...)", "for (...)", "switch (...)"...
    ['if', '$a && $b'],
    ['elseif', '$a && $b'],
    ['unless', '$a && $b'],
    ['while', '$a && $b'],
    ['for', '$i = 0; $i < 10; $i++'],
    ['foreach', '$users as $user'],
    ['forelse', '$users as $user'],
    ['switch', '$status'],
    ['case', "'published'"],
    // Compiled to "if (...): echo 'checked'; endif"...
    ['checked', '$active && $enabled'],
    ['selected', '$active && $enabled'],
    ['disabled', '$active && $enabled'],
    ['readonly', '$active && $enabled'],
    ['required', '$active && $enabled'],
    // Compiled to "if (...) break;" and "if (...) continue;"...
    ['break', '$loop->index > 3'],
    ['continue', '$loop->first'],
    // Compiled to a language construct or a raw statement...
    ['isset', '$user->name'],
    ['empty', '$users'],
    ['unset', '$user'],
    ['php', '$foo = 1'],
    ['use', "'App\\Models\\User'"],
]);

it('adds a comma to a nested array but never to its enclosing condition', function () {
    $in = "@if (in_array(\$x, [\n    'a',\n    'b'\n]))\n@endif\n";
    $out = "@if (in_array(\$x, [\n    'a',\n    'b',\n]))\n@endif\n";

    expect((new DirectiveTrailingCommas)->postFormat($in))->toBe($out);
});

it('adds commas at each multiline level of nested arrays', function () {
    $in = "@props([\n    'list' => [\n        'a',\n        'b'\n    ]\n])\n";
    $out = "@props([\n    'list' => [\n        'a',\n        'b',\n    ],\n])\n";

    expect((new DirectiveTrailingCommas)->postFormat($in))->toBe($out);
});

it('does not corrupt strings that contain commas or brackets', function () {
    $in = "@props([\n    'csv' => 'a, b, c',\n    'br' => 'x[0], y(1)',\n    'q' => 'it\\'s, fine'\n])\n";
    $out = "@props([\n    'csv' => 'a, b, c',\n    'br' => 'x[0], y(1)',\n    'q' => 'it\\'s, fine',\n])\n";

    expect((new DirectiveTrailingCommas)->postFormat($in))->toBe($out);
});

it('leaves an empty multiline array alone', function () {
    $in = "@props([\n])\n";

    expect((new DirectiveTrailingCommas)->postFormat($in))->toBe($in);
});

it('does not touch @php blocks or raw php tags', function () {
    $php = "@php\n\$x = [\n    1,\n    2\n];\n@endphp\n";
    $tag = "<?php \$x = [\n    1,\n    2\n]; ?>\n";

    expect((new DirectiveTrailingCommas)->postFormat($php))->toBe($php)
        ->and((new DirectiveTrailingCommas)->postFormat($tag))->toBe($tag);
});

it('does not treat echo contents as directive arguments', function () {
    $in = "{{ \$items->map(fn (\$i) => [\n    \$i\n]) }}\n";

    expect((new DirectiveTrailingCommas)->postFormat($in))->toBe($in);
});

it('ignores escaped @@ directives', function () {
    $in = "@@props([\n    'a' => 1\n])\n";

    expect((new DirectiveTrailingCommas)->postFormat($in))->toBe($in);
});

it('is idempotent', function () {
    $in = "@props([\n    'a' => 1\n])\n";
    $once = (new DirectiveTrailingCommas)->postFormat($in);

    expect((new DirectiveTrailingCommas)->postFormat($once))->toBe($once)
        ->and($once)->toBe("@props([\n    'a' => 1,\n])\n");
});
