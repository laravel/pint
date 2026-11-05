<?php

use App\Repositories\ConfigurationJsonRepository;
use App\Repositories\GitPathsRepository;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

uses(TestCase::class)->beforeEach(function () {
    $this->root = sys_get_temp_dir().'/pint-git-paths-'.bin2hex(random_bytes(6));
    $this->cwd = getcwd();

    app()->bind(ConfigurationJsonRepository::class, fn () => new ConfigurationJsonRepository(null, null));

    mkdir($this->root, 0777, true);
    chdir($this->root);

    file_put_contents($this->root.'/old.php', "<?php\n\n\$foo = 'bar';\n");

    (new Process(['git', 'init', '--quiet']))->mustRun();
    (new Process(['git', 'add', '.']))->mustRun();
    (new Process(['git', '-c', 'user.name=Pint', '-c', 'user.email=pint@example.com', 'commit', '--quiet', '-m', 'Initial commit']))->mustRun();
});

afterEach(function () {
    chdir($this->cwd);

    File::deleteDirectory($this->root);
});

it('includes renamed files', function () {
    (new Process(['git', 'mv', 'old.php', 'new.php']))->mustRun();

    expect((new GitPathsRepository($this->root))->dirty())->toBe([
        $this->root.DIRECTORY_SEPARATOR.'new.php',
    ]);
});

it('includes renamed files that were modified after the rename', function () {
    (new Process(['git', 'mv', 'old.php', 'new.php']))->mustRun();

    file_put_contents($this->root.'/new.php', "<?php\n\n\$foo = 'baz';\n");

    expect((new GitPathsRepository($this->root))->dirty())->toBe([
        $this->root.DIRECTORY_SEPARATOR.'new.php',
    ]);
});
