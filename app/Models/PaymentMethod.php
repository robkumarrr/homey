<?php

namespace App\Models;

use App\Concerns\BelongsToFamily;
use App\Enums\PaymentMethodKind;
use Carbon\CarbonImmutable;
use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A specific bank account or credit card the family pays from. Only a
 * nickname and optionally the last four digits, never a full number.
 *
 * @property int $id
 * @property int $family_id
 * @property string $name
 * @property PaymentMethodKind $kind
 * @property string|null $last_four
 * @property int $position
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'kind', 'last_four', 'position'])]
class PaymentMethod extends Model
{
    use BelongsToFamily;

    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => PaymentMethodKind::class,
            'archived_at' => 'datetime',
        ];
    }
}
