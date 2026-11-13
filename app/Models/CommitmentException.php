<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A change to one of a bill's due dates, keyed on the date the schedule gives
 * it (ADR 0001). For now that's a different amount for just that date.
 *
 * @property int $id
 * @property int $commitment_id
 * @property CarbonImmutable $original_due_on
 * @property int|null $amount_cents
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['original_due_on', 'amount_cents'])]
class CommitmentException extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'original_due_on' => 'date:Y-m-d',
            'amount_cents' => 'integer',
        ];
    }
}
