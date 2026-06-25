# Blade formatting fixtures

Every directory here is a **concern** — one slice of the Blade formatter's
behaviour. Each concern is exercised by a matching test file in
`tests/Feature/Blade/<Concern>Test.php` (a one-liner: `bladeFixtureTest('name')`).
Keeping one concern per test file lets the parallel test runner spread the
(node-bound, otherwise slow) formatting work across cores instead of
serialising it behind a single dataset.

## How a fixture works

Each fixture is a pair:

- `name.blade.php` — the **input**, as a developer might write it.
- `name.blade.php.expected` — the **golden** output the formatter must produce.

For every fixture the suite asserts two things from a single Pint run:

1. **Golden** — formatting the input yields the `.expected` file.
2. **Idempotent** — formatting the `.expected` file leaves it unchanged.

Drop a new `name.blade.php` + `name.blade.php.expected` into the right concern
folder and it is picked up automatically — no test file edits needed. Adding a
brand-new concern folder requires a one-line test file (the `CoverageTest`
guard fails until it exists).

## Concerns

| Concern | What it covers |
| --- | --- |
| `alpine` | Alpine.js expressions in attributes (`x-data`, `:class`, nested quotes). |
| `attributes` | Attribute wrapping/collapsing, interpolated values, `wire:` directives. |
| `comments` | Blade `{{-- --}}` and HTML `<!-- -->` comments, single- and multi-line. |
| `components` | `<x-*>`, `<flux:*>`, `<livewire:*>`, slots, dynamic & nested components. |
| `control-structures` | `@if`/`@foreach`/`@isset`/… indentation, inline vs. block, deep nesting. |
| `directives` | Directive spacing/normalisation, `@class`/`@checked` attributes, `@dump`. |
| `echoes` | `{{ }}`, `{!! !!}`, long and multi-line echoes, expression spacing. |
| `error-directives` | `@error`/`@session` blocks and graceful handling of unclosed ones. |
| `escaping` | `@@`/`@{{ }}` escapes and `@verbatim` blocks left untouched. |
| `html` | Document/`<head>`/`<body>` structure, void elements, tables, entities, SVG. |
| `ignorables` | Files Pint must skip: `node_modules`, `storage`, mail, Boost, Envoy. |
| `inline` | Inline collapsing/padding of slots, options, prose, SVG, paragraph reflow. |
| `kitchen-sink` | A realistic Volt component combining many concerns end to end. |
| `minimal` | Degenerate inputs: empty, comment-only and text-only files. |
| `normalization` | Whitespace normalisation: tabs→spaces, CRLF→LF, blank-line collapse, trailing newline. |
| `not-operator` | `!` → `! ` spacing in Blade and JavaScript expressions. |
| `php` | `@php`/`<?php` blocks, SFC class bodies, heredoc, raw-php islands. |
| `script` | `<script>` contents: echoes, directives, modules, raw PHP. |
| `style` | `<style>` block structure, CSS at-rules and surrounding markup. |
| `style-dynamic` | Blade/PHP/echo interpolated into CSS values and rules. |
| `tailwind` | Tailwind class sorting (with and without surrounding `<style>`). |
| `trailing` | Trailing commas/structure in `@props`, `@include`, array arguments. |
| `whitespace` | Whitespace-*sensitive* elements (`<pre>`, `<textarea>`, `<code>`) preserved. |

> `ignorables` is special: those fixtures rely on their real relative paths
> (`node_modules/…`, `resources/views/emails/…`, `Envoy.blade.php`, …) so Pint's
> finder/ignore rules trigger. They are staged at the project root and must come
> out unchanged.
