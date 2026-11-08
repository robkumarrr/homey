<?php

namespace App\Models;

use App\Concerns\BelongsToFamily;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Money that actually left the household. The only record of real spending
 * (ADR 0002); create it through RecordExpense.
 *
 * @property int $id
 * @property int $family_id
 * @property int $category_id
 * @property int|null $created_by
 * @property int $amount_cents
 * @property Carbon $paid_on
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category $category
 */
#[Fillable(['category_id', 'amount_cents', 'paid_on', 'note'])]
class Expense extends Model
{
    use BelongsToFamily;

    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'paid_on' => 'date:Y-m-d',
        ];
    }
}
