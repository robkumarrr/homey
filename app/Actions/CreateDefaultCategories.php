<?php

namespace App\Actions;

use App\Enums\CategoryColour;
use App\Enums\CategoryIcon;
use App\Models\Family;

class CreateDefaultCategories
{
    /**
     * The starter set every new family gets. Families own their copies, so
     * renaming or archiving one never touches another family.
     *
     * @var list<array{0: string, 1: CategoryIcon, 2: CategoryColour}>
     */
    public const DEFAULTS = [
        ['Housing', CategoryIcon::House, CategoryColour::Blue],
        ['Groceries', CategoryIcon::ShoppingCart, CategoryColour::Emerald],
        ['Dining', CategoryIcon::UtensilsCrossed, CategoryColour::Pink],
        ['Transport', CategoryIcon::Car, CategoryColour::Sky],
        ['Utilities', CategoryIcon::Zap, CategoryColour::Yellow],
        ['Subscriptions', CategoryIcon::Tv, CategoryColour::Violet],
        ['Pets', CategoryIcon::PawPrint, CategoryColour::Teal],
        ['Home & projects', CategoryIcon::Hammer, CategoryColour::Amber],
        ['Other', CategoryIcon::CircleEllipsis, CategoryColour::Zinc],
    ];

    /**
     * Copy the default categories into the given family.
     */
    public function handle(Family $family): void
    {
        foreach (self::DEFAULTS as $position => [$name, $icon, $colour]) {
            $family->categories()->create([
                'name' => $name,
                'icon' => $icon,
                'colour' => $colour,
                'position' => $position,
            ]);
        }
    }
}
