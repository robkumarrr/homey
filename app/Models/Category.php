<?php

namespace App\Models;

use App\Concerns\BelongsToFamily;
use App\Enums\CategoryColour;
use App\Enums\CategoryIcon;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $family_id
 * @property string $name
 * @property CategoryIcon $icon
 * @property CategoryColour $colour
 * @property int $position
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'icon', 'colour', 'position'])]
class Category extends Model
{
    use BelongsToFamily;

    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'icon' => CategoryIcon::class,
            'colour' => CategoryColour::class,
            'archived_at' => 'datetime',
        ];
    }
}
