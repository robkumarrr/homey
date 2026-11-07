<?php

namespace App\Concerns;

use App\Models\Family;
use App\Models\Scopes\FamilyScope;
use App\Support\Tenancy\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * For models owned by a family: reads are limited to the resolved family and
 * new records are assigned to it unless a family_id is set explicitly.
 *
 * Escape the scope only with withoutGlobalScope(FamilyScope::class), never
 * withoutGlobalScopes().
 *
 * @property int $family_id
 * @property-read Family $family
 */
trait BelongsToFamily
{
    public static function bootBelongsToFamily(): void
    {
        static::addGlobalScope(new FamilyScope);

        static::creating(function (self $model): void {
            $model->family_id ??= app(Tenant::class)->familyId();
        });
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}
