<?php

namespace App\Fixers\Utils;

class PhpdocTagComparator
{
    /**
     * Groups of tags that should be allowed to immediately follow each other.
     *
     * @var array<string[]>
     */
    private static $groups = [
        ['deprecated', 'link', 'see', 'since'],
        ['author', 'copyright', 'license'],
        ['category', 'package', 'subpackage'],
        ['property', 'property-read', 'property-write'],
        ['param', 'return'],
    ];

    /**
     * Decide if the the given tags be kept together or kept apart?
     *
     * @param  \PhpCsFixer\DocBlock\Tag $first
     * @param  \PhpCsFixer\DocBlock\Tag $second
     * @return bool
     */
    public static function shouldBeTogether($first, $second)
    {
        $firstName = $first->getName();
        $secondName = $second->getName();

        if ($firstName === $secondName) {
            return true;
        }

        foreach (self::$groups as $group) {
            if (\in_array($firstName, $group, true) && \in_array($secondName, $group, true)) {
                return true;
            }
        }

        return false;
    }
}
