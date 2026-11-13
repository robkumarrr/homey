<?php

namespace App\Concerns;

use App\Support\Tenancy\Tenant;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Rules for picking one of the family's categories or payment methods. The
 * pick must belong to the current family (ADR 0006) and be active, unless a
 * record being edited keeps the archived one it already had.
 */
trait PicksFamilyRecords
{
    protected function activeCategory(?int $keepId = null): Exists
    {
        return $this->activeFamilyRecord('categories', $keepId);
    }

    protected function activePaymentMethod(?int $keepId = null): Exists
    {
        return $this->activeFamilyRecord('payment_methods', $keepId);
    }

    private function activeFamilyRecord(string $table, ?int $keepId): Exists
    {
        return Rule::exists($table, 'id')
            ->where('family_id', app(Tenant::class)->familyId())
            ->where(fn (Builder $query) => $query
                ->whereNull('archived_at')
                ->when($keepId, fn (Builder $query, int $id) => $query->orWhere('id', $id)));
    }
}
