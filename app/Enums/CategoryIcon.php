<?php

namespace App\Enums;

/**
 * The icon on a category's badge. Values are Lucide icon names, which the
 * frontend maps to components.
 */
enum CategoryIcon: string
{
    case House = 'house';
    case ShoppingCart = 'shopping-cart';
    case UtensilsCrossed = 'utensils-crossed';
    case Car = 'car';
    case Zap = 'zap';
    case Tv = 'tv';
    case PawPrint = 'paw-print';
    case Hammer = 'hammer';
    case CircleEllipsis = 'circle-ellipsis';
}
