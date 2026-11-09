<?php

namespace App\Models;

use App\Concerns\BelongsToFamily;
use App\Models\Scopes\FamilyScope;
use Carbon\CarbonImmutable;
use Database\Factories\DeletedExpenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * A deleted expense waiting in Recently deleted (ADR 0011). The payload is a
 * snapshot of the expense row, so restoring puts it back exactly as it was.
 *
 * @property int $id
 * @property int $family_id
 * @property int $expense_id
 * @property array<string, mixed> $payload
 * @property int|null $deleted_by
 * @property CarbonImmutable $deleted_at
 */
class DeletedExpense extends Model
{
    use BelongsToFamily;

    /** @use HasFactory<DeletedExpenseFactory> */
    use HasFactory;

    use MassPrunable;

    /** How long a deleted expense can be restored. */
    public const KEEP_DAYS = 30;

    public $timestamps = false;

    /**
     * Purge entries older than KEEP_DAYS, across every family: the scheduled
     * prune runs with no family resolved, so it names the one scope it skips.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return $this->newQuery()
            ->withoutGlobalScope(FamilyScope::class)
            ->where('deleted_at', '<=', now()->subDays(self::KEEP_DAYS));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'deleted_at' => 'datetime',
        ];
    }
}
