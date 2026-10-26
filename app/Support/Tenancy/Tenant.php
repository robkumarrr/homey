<?php

namespace App\Support\Tenancy;

use App\Models\Family;
use App\Models\House;
use Closure;

/**
 * The family (and its current house) that this request or job works within.
 *
 * Bound as a scoped singleton: requests resolve it in ResolveTenant, while
 * jobs, seeders and commands set it explicitly with for().
 */
class Tenant
{
    private ?Family $family = null;

    private ?House $house = null;

    public function set(?Family $family): void
    {
        $this->family = $family;
        $this->house = null;
    }

    public function resolved(): bool
    {
        return $this->family !== null;
    }

    /**
     * @throws TenantNotResolved
     */
    public function family(): Family
    {
        return $this->family ?? throw TenantNotResolved::make();
    }

    /**
     * @throws TenantNotResolved
     */
    public function familyId(): int
    {
        return $this->family()->id;
    }

    /**
     * The house the family is working in. One house per family for now, so
     * this is the family's first house that is not archived.
     *
     * @throws TenantNotResolved
     */
    public function house(): House
    {
        return $this->house ??= $this->family()->houses()
            ->whereNull('archived_at')
            ->oldest('id')
            ->firstOrFail();
    }

    /**
     * Run a callback as the given family, restoring the previous one after.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function for(Family $family, Closure $callback): mixed
    {
        [$previousFamily, $previousHouse] = [$this->family, $this->house];

        $this->set($family);

        try {
            return $callback();
        } finally {
            [$this->family, $this->house] = [$previousFamily, $previousHouse];
        }
    }
}
