<?php

namespace App\Http\Controllers\Finances;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Expense;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancesController extends Controller
{
    /**
     * Show one month of spending, the current month by default. Expenses are
     * counted in the month they were paid (cash basis, ADR 0009).
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
            ->get(['id', 'category_id', 'amount_cents', 'paid_on', 'note']);

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
            'expenses' => $expenses,
            'spentCents' => (int) $expenses->sum('amount_cents'),
        ]);
    }
}
