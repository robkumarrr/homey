<?php

namespace App\Models\Scopes;

use App\Support\Tenancy\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limits family-owned models to the resolved family, and throws when none is.
 *
 * @implements Scope<Model>
 */
class FamilyScope implements Scope
{
    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->qualifyColumn('family_id'), app(Tenant::class)->familyId());
    }
}
