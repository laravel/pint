<?php

namespace App\Fixers\Utils;

use PhpCsFixer\DocBlock\Tag;

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
     * Should the given tags be kept together, or kept apart?
     *
     * @param  Tag $first
     * @param  Tag $second
     * @return bool
     */
    public static function shouldBeTogether(Tag $first, Tag $second): bool
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
