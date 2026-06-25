<?php

use App\Fixers\LaravelBlade\Ignorables\BoostGuidelines;
use App\Fixers\LaravelBlade\Ignorables\EmailView;
use App\Fixers\LaravelBlade\Ignorables\Envoy;

it('ignores envoy files by name', function (string $path, bool $expected) {
    expect((new Envoy)($path))->toBe($expected);
})->with([
    ['/app/Envoy.blade.php', true],
    ['/app/envoy.blade.php', true],
    ['/resources/views/welcome.blade.php', false],
    ['/resources/views/envoy/show.blade.php', false],
]);

it('ignores boost guidelines by path', function (string $path, bool $expected) {
    expect((new BoostGuidelines)($path))->toBe($expected);
})->with([
    ['/app/resources/boost/guidelines/core.blade.php', true],
    ['/app/resources/boost/guidelines/nested/foo.blade.php', true],
    ['C:\\app\\resources\\boost\\guidelines\\core.blade.php', true],
    ['/app/resources/views/welcome.blade.php', false],
    ['/app/resources/boost/other.blade.php', false],
]);

it('ignores email views by path', function (string $path, bool $expected) {
    expect((new EmailView)($path))->toBe($expected);
})->with([
    ['/app/resources/views/emails/notification.blade.php', true],
    ['/app/resources/views/emails/nested/welcome.blade.php', true],
    ['C:\\app\\resources\\views\\emails\\notification.blade.php', true],
    ['/app/resources/views/mail/notification.blade.php', true],
    ['/app/resources/views/mail/nested/welcome.blade.php', true],
    ['C:\\app\\resources\\views\\mail\\notification.blade.php', true],
    ['/app/resources/views/welcome.blade.php', false],
    ['/app/resources/views/email.blade.php', false],
]);
