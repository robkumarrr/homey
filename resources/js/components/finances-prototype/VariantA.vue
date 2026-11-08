<script setup lang="ts">
/**
 * PROTOTYPE variant A — "Ledger": three totals up top, then bills and expenses
 * side by side, then a category table. Everything for the month on one screen.
 */
import { Check, Plus, Repeat, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { formatDay, money } from './format';
import MonthSwitcher from './MonthSwitcher.vue';
import {
    addingExpense,
    byCategory,
    categoryName,
    daysBetween,
    deleteExpense,
    monthExpenses,
    monthOccurrences,
    paying,
    paymentMethodName,
    today,
    toggleSkip,
    totals,
} from './store';
import type { Occurrence } from './store';

function dueText(occurrence: Occurrence): string {
    if (occurrence.status === 'paid') {
        return `Paid ${formatDay(occurrence.expense!.paidOn)} · ${paymentMethodName(occurrence.expense!.paymentMethodId)}`;
    }

    if (occurrence.status === 'skipped') {
        return 'Skipped this month';
    }

    const days = daysBetween(today, occurrence.dueOn);

    if (days < 0) {
        return `${-days} day${days === -1 ? '' : 's'} overdue`;
    }

    return days === 0 ? 'Due today' : `Due ${formatDay(occurrence.dueOn)}`;
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold tracking-tight md:text-3xl">
                Finances
            </h1>
            <div class="flex items-center gap-2">
                <MonthSwitcher />
                <Button size="sm" @click="addingExpense = true">
                    <Plus /> Expense
                </Button>
            </div>
        </div>

        <section class="grid grid-cols-3 gap-3">
            <div class="rounded-2xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Spent so far</p>
                <p class="mt-1 text-lg font-semibold tabular-nums md:text-2xl">
                    {{ money(totals.spent) }}
                </p>
            </div>
            <div class="rounded-2xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Bills still due</p>
                <p class="mt-1 text-lg font-semibold tabular-nums md:text-2xl">
                    {{ money(totals.stillDue) }}
                </p>
            </div>
            <div class="rounded-2xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Projected</p>
                <p class="mt-1 text-lg font-semibold tabular-nums md:text-2xl">
                    {{ money(totals.projected) }}
                </p>
            </div>
        </section>

        <div class="grid gap-6 md:grid-cols-2">
            <section class="rounded-2xl border bg-card">
                <h2 class="border-b px-4 py-3 text-sm font-semibold">
                    Bills this month
                </h2>
                <ul class="divide-y">
                    <li
                        v-for="occurrence in monthOccurrences"
                        :key="occurrence.key"
                        class="flex items-center gap-3 px-4 py-3"
                        :class="occurrence.status === 'skipped' && 'opacity-50'"
                    >
                        <div class="min-w-0 flex-1">
                            <p
                                class="flex items-center gap-1.5 text-sm font-medium"
                            >
                                <span
                                    :class="
                                        occurrence.status === 'skipped' &&
                                        'line-through'
                                    "
                                    >{{ occurrence.bill.name }}</span
                                >
                                <Repeat
                                    v-if="occurrence.bill.autopay"
                                    class="size-3.5 text-muted-foreground"
                                    aria-label="Autopay"
                                />
                            </p>
                            <p
                                class="text-xs"
                                :class="
                                    occurrence.status === 'overdue'
                                        ? 'font-medium text-destructive'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ dueText(occurrence) }}
                            </p>
                        </div>
                        <span class="text-sm tabular-nums">
                            <span
                                v-if="
                                    occurrence.bill.variable &&
                                    occurrence.status !== 'paid'
                                "
                                class="text-muted-foreground"
                                >~</span
                            >{{ money(occurrence.amountCents) }}
                        </span>
                        <Check
                            v-if="occurrence.status === 'paid'"
                            class="size-4 text-(--meter-ok)"
                        />
                        <template v-else>
                            <Button
                                v-if="occurrence.status !== 'skipped'"
                                size="sm"
                                variant="outline"
                                @click="paying = occurrence"
                            >
                                Paid
                            </Button>
                            <button
                                type="button"
                                class="text-xs text-muted-foreground hover:text-foreground"
                                @click="toggleSkip(occurrence)"
                            >
                                {{
                                    occurrence.status === 'skipped'
                                        ? 'Undo'
                                        : 'Skip'
                                }}
                            </button>
                        </template>
                    </li>
                </ul>
            </section>

            <section class="rounded-2xl border bg-card">
                <h2 class="border-b px-4 py-3 text-sm font-semibold">
                    Expenses
                    <span class="font-normal text-muted-foreground">
                        · {{ monthExpenses.length }}
                    </span>
                </h2>
                <p
                    v-if="monthExpenses.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    Nothing spent this month.
                </p>
                <ul class="max-h-[32rem] divide-y overflow-y-auto">
                    <li
                        v-for="expense in monthExpenses"
                        :key="expense.id"
                        class="group flex items-center gap-3 px-4 py-2.5"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm">
                                {{
                                    expense.note ||
                                    categoryName(expense.categoryId)
                                }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDay(expense.paidOn) }} ·
                                {{ categoryName(expense.categoryId) }} ·
                                {{ paymentMethodName(expense.paymentMethodId) }}
                            </p>
                        </div>
                        <span class="text-sm tabular-nums">
                            {{ money(expense.amountCents) }}
                        </span>
                        <button
                            type="button"
                            class="text-muted-foreground hover:text-destructive focus:opacity-100 md:opacity-0 md:group-hover:opacity-100"
                            aria-label="Delete expense"
                            @click="deleteExpense(expense)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </li>
                </ul>
            </section>
        </div>

        <section class="rounded-2xl border bg-card">
            <h2 class="border-b px-4 py-3 text-sm font-semibold">
                By category
            </h2>
            <table class="w-full text-sm">
                <thead class="text-xs text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 text-left font-normal">
                            Category
                        </th>
                        <th class="px-4 py-2 text-right font-normal">Spent</th>
                        <th class="px-4 py-2 text-right font-normal">
                            Still due
                        </th>
                        <th class="px-4 py-2 text-right font-normal">
                            Projected
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y tabular-nums">
                    <tr v-for="row in byCategory" :key="row.id">
                        <td class="px-4 py-2">{{ row.name }}</td>
                        <td class="px-4 py-2 text-right">
                            {{ money(row.spent) }}
                        </td>
                        <td class="px-4 py-2 text-right text-muted-foreground">
                            {{ row.due ? money(row.due) : '—' }}
                        </td>
                        <td class="px-4 py-2 text-right font-medium">
                            {{ money(row.spent + row.due) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
