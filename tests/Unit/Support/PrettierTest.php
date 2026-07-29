<?php

use App\Support\Prettier;

it('does not start the worker for content without ignore range markers', function () {
    $prettier = new Prettier('/missing-project');

    expect($prettier->ignoreRanges('view.blade.php', '<div>Content</div>'))->toBe([]);
});
