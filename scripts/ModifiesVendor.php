<?php

namespace Scripts;

use Composer\Script\Event;

class ModifiesVendor
{
    /**
     * Handle the post-autoload-dump Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postAutoloadDump(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        static::modifyBracesFixer();
    }

    /**
     * Modify the source code of the PHP CS Fixer braces fixer.
     *
     * @return void
     */
    protected static function modifyBracesFixer()
    {
        $path = __DIR__.'/../vendor/friendsofphp/php-cs-fixer/src/Fixer/Basic/BracesFixer.php';

        $content = file_get_contents($path);

        $content = str_replace(
            <<<'EOF'
                            $token->isGivenKind(T_FUNCTION) && $tokensAnalyzer->isLambda($index)
EOF,
            <<<'EOF'
                            (resolve(\App\Repositories\ConfigurationJsonRepository::class)->preset() != 'laravel' && $token->isGivenKind(T_FUNCTION) && $tokensAnalyzer->isLambda($index))
EOF,
            $content
        );

        file_put_contents($path, $content);
    }
}
