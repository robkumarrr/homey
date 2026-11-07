<?php

namespace App\Models;

use App\Enums\FamilyRole;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * A member's place in a family (ADR 0008).
 *
 * @property int $id
 * @property int $family_id
 * @property int $user_id
 * @property FamilyRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Membership extends Pivot
{
    protected $table = 'family_user';

    public $incrementing = true;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => FamilyRole::class,
        ];
    }
}
