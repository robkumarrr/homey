<?php

namespace App\Enums;

/**
 * The colour tone a category's badge is drawn in. The frontend maps each
 * value to its light and dark mode classes.
 */
enum CategoryColour: string
{
    case Blue = 'blue';
    case Emerald = 'emerald';
    case Pink = 'pink';
    case Sky = 'sky';
    case Yellow = 'yellow';
    case Violet = 'violet';
    case Teal = 'teal';
    case Amber = 'amber';
    case Zinc = 'zinc';
}
