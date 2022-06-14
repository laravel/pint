<?php

it('inspire artisans', function () {
    $this->artisan('lint')->assertExitCode(0);
});
