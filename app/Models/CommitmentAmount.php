<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A bill's amount from one of its due dates onward, such as a mortgage after
 * a rate change. Earlier dates keep the amount before it (ADR 0012).
 *
 * @property int $id
 * @property int $commitment_id
 * @property CarbonImmutable $effective_on
 * @property int $amount_cents
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['effective_on', 'amount_cents'])]
class CommitmentAmount extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_on' => 'date:Y-m-d',
            'amount_cents' => 'integer',
        ];
    }
}
