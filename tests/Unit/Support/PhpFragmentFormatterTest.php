<?php

use App\Repositories\ConfigurationJsonRepository;
use App\Support\PhpFragmentFormatter;
use Tests\TestCase;

/*
| The formatter reads the active preset off the configuration repository, which
| is normally built from the command's input. There is no command here, so the
| repository is bound by hand to the preset Pint defaults to.
*/
uses(TestCase::class)->beforeEach(function () {
    app()->singleton(
        ConfigurationJsonRepository::class,
        fn () => new ConfigurationJsonRepository(null, 'laravel'),
    );
});

it('keeps the imports of a "<?php ... ?>" island it formats', function () {
    // The island is formatted on its own, so the markup that uses "CalculationMode"
    // is as far out of sight here as it is for the run over the whole file.
    $in = <<<'PHP'
    <?php

    use App\Enums\CalculationMode;
    use App\Models\User;

    $default = "all";
    ?>

    PHP;

    $out = (new PhpFragmentFormatter)->format($in);

    expect($out)->toContain('use App\Enums\CalculationMode;')
        ->toContain('use App\Models\User;')
        // The rest of the preset still runs over the island.
        ->toContain("\$default = 'all';");
});

it('keeps the imports of a fragment it formats', function () {
    $in = <<<'PHP'
    <?php
    use App\Models\User;
    $default = "all";

    PHP;

    $out = (new PhpFragmentFormatter)->format($in, fragment: true);

    expect($out)->toContain('use App\Models\User;')
        ->toContain("\$default = 'all';");
});

it('keeps the imports of a single file component it formats', function () {
    $in = <<<'PHP'
    <?php

    use App\Models\User;
    use Illuminate\View\Component;

    new class extends Component
    {
        public string $mode = "all";
    };
    ?>

    PHP;

    $out = (new PhpFragmentFormatter)->format($in);

    expect($out)->toContain('use App\Models\User;')
        ->toContain('use Illuminate\View\Component;')
        ->toContain("public string \$mode = 'all';");
});

it('formats an island the same way twice', function () {
    $in = <<<'PHP'
    <?php

    use App\Enums\CalculationMode;

    $default = "all";
    ?>

    PHP;

    $formatter = new PhpFragmentFormatter;

    $once = $formatter->format($in);

    expect($formatter->format($once))->toBe($once);
});
