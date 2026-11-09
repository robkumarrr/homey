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
    case Shirt = 'shirt';
    case HeartPulse = 'heart-pulse';
    case Baby = 'baby';
    case GraduationCap = 'graduation-cap';
    case Gift = 'gift';
    case Plane = 'plane';
    case Fuel = 'fuel';
    case Dumbbell = 'dumbbell';
    case Coffee = 'coffee';
    case Wifi = 'wifi';
    case Shield = 'shield';
    case PiggyBank = 'piggy-bank';
    case Landmark = 'landmark';
    case Wrench = 'wrench';
    case Sprout = 'sprout';
    case Sofa = 'sofa';
    case Gamepad2 = 'gamepad-2';
    case HandHeart = 'hand-heart';
}
