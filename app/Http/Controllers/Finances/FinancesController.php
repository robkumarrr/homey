<?php

namespace App\Http\Controllers\Finances;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Commitment;
use App\Models\DeletedExpense;
use App\Models\Expense;
use App\Models\PaymentMethod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancesController extends Controller
{
    /**
     * Show one month of bills and spending, the current month by default.
     * Expenses are counted in the month they were paid (cash basis, ADR 0009),
     * and bill dates are computed from each schedule, never stored (ADR 0002).
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = isset($validated['month'])
            ? CarbonImmutable::createFromFormat('!Y-m', $validated['month'])
            : CarbonImmutable::now()->startOfMonth();

        $expenses = Expense::query()
            ->whereBetween('paid_on', [$month->toDateString(), $month->endOfMonth()->toDateString()])
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->get(['id', 'name', 'category_id', 'payment_method_id', 'amount_cents', 'paid_on']);

        $commitments = Commitment::query()->with(['amounts', 'exceptions'])->orderBy('name')->get();
        $today = CarbonImmutable::today();

        return Inertia::render('Finances', [
            'month' => $month->format('Y-m'),
            'categories' => Category::query()
                ->orderBy('position')
                ->orderBy('name')
                ->get(['id', 'name', 'icon', 'colour', 'archived_at'])
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'colour' => $category->colour,
                    'archived' => $category->archived_at !== null,
                ]),
            'paymentMethods' => PaymentMethod::query()
                ->orderBy('position')
                ->orderBy('name')
                ->get(['id', 'name', 'kind', 'last_four', 'archived_at'])
                ->map(fn (PaymentMethod $method) => [
                    'id' => $method->id,
                    'name' => $method->name,
                    'kind' => $method->kind,
                    'last_four' => $method->last_four,
                    'archived' => $method->archived_at !== null,
                ]),
            'commitments' => $commitments->map(fn (Commitment $commitment) => $this->bill($commitment, $today)),
            'occurrences' => $commitments
                ->flatMap(fn (Commitment $commitment) => array_map(
                    fn (CarbonImmutable $dueOn) => [
                        'commitment_id' => $commitment->id,
                        'due_on' => $dueOn->toDateString(),
                        'amount_cents' => $commitment->amountDueOn($dueOn),
                    ],
                    $commitment->recurrence()->between($month, $month->endOfMonth()),
                ))
                ->sortBy('due_on')
                ->values(),
            'expenses' => $expenses,
            'spentCents' => (int) $expenses->sum('amount_cents'),
            'deletedExpenses' => DeletedExpense::query()
                ->latest('deleted_at')
                ->latest('id')
                ->limit(100)
                ->get()
                ->map(fn (DeletedExpense $deleted) => [
                    'id' => $deleted->id,
                    'name' => $deleted->payload['name'],
                    'amount_cents' => $deleted->payload['amount_cents'],
                    'category_id' => $deleted->payload['category_id'],
                    'paid_on' => $deleted->payload['paid_on'],
                    'deleted_at' => $deleted->deleted_at->toIso8601String(),
                ]),
        ]);
    }

    /**
     * A bill for the All bills sheet and the dialogs, with its next due date
     * from today and what's owed then.
     *
     * @return array<string, mixed>
     */
    private function bill(Commitment $commitment, CarbonImmutable $today): array
    {
        $nextDueOn = $commitment->recurrence()->nextOnOrAfter($today);

        return [
            'id' => $commitment->id,
            'name' => $commitment->name,
            'category_id' => $commitment->category_id,
            'payment_method_id' => $commitment->payment_method_id,
            'amount_varies' => $commitment->amount_varies,
            'frequency' => $commitment->frequency,
            'interval' => $commitment->interval,
            'starts_on' => $commitment->starts_on->toDateString(),
            'ends_on' => $commitment->ends_on?->toDateString(),
            'archived' => $commitment->archived_at !== null,
            'next_due_on' => $nextDueOn?->toDateString(),
            'next_amount_cents' => $nextDueOn === null ? null : $commitment->amountDueOn($nextDueOn),
        ];
    }
}
