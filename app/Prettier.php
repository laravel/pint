<?php

namespace App;

class Prettier
{
    /**
     * The node sandbox instance.
     *
     * @var \App\NodeSandbox
     */
    protected $sandbox;

    /**
     * The node sandbox instance.
     *
     * @param  \App\NodeSandbox  $sandbox
     * @return void
     */
    public function __construct($sandbox)
    {
        $this->sandbox = $sandbox;
    }

    /**
     * Run the prettier command.
     *
     * @param  array<int, string>  $params
     * @return \Illuminate\Process\ProcessResult
     */
    public function run($params = [])
    {
        return $this->sandbox->run([
            './node_modules/.bin/prettier',
            '--config',
            $this->sandbox->path().'/'.'.prettierrc',
            ...$params,
        ]);
    }
}
