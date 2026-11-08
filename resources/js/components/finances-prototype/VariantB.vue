<script setup lang="ts">
/**
 * PROTOTYPE variant B — "Statement": one chronological feed for the month, like
 * a bank statement that also knows what's coming. Overdue bills pinned on top,
 * upcoming bills above a "today" line, past days below. Floating + to add.
 */
import { CircleAlert, Plus, Repeat } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { formatDay, money } from './format';
import MonthSwitcher from './MonthSwitcher.vue';
import {
    addingExpense,
    categoryName,
    deleteExpense,
    monthExpenses,
    monthOccurrences,
    paying,
    paymentMethodName,
    today,
    toggleSkip,
    totals,
} from './store';
import type { Expense, Occurrence } from './store';

type Filter = 'all' | 'bills' | 'spending';
const filter = ref<Filter>('all');

type Row =
    | { kind: 'expense'; date: string; expense: Expense }
    | { kind: 'occurrence'; date: string; occurrence: Occurrence };

const overdue = computed(() =>
    monthOccurrences.value.filter((o) => o.status === 'overdue'),
);

const rows = computed<Row[]>(() => {
    const expenseRows: Row[] = monthExpenses.value
        .filter((e) =>
            filter.value === 'all'
                ? true
                : filter.value === 'bills'
                  ? e.billId !== null
                  : e.billId === null,
        )
        .map((expense) => ({ kind: 'expense', date: expense.paidOn, expense }));

    const occurrenceRows: Row[] =
        filter.value === 'spending'
            ? []
            : monthOccurrences.value
                  .filter((o) => o.status === 'due' || o.status === 'skipped')
                  .map((occurrence) => ({
                      kind: 'occurrence',
                      date: occurrence.dueOn,
                      occurrence,
                  }));

    return [...expenseRows, ...occurrenceRows];
});

const upcomingDays = computed(() =>
    groupByDay(
        rows.value.filter((r) => r.date > today),
        'asc',
    ),
);
const pastDays = computed(() =>
    groupByDay(
        rows.value.filter((r) => r.date <= today),
        'desc',
    ),
);

function groupByDay(list: Row[], order: 'asc' | 'desc') {
    const days = new Map<string, Row[]>();

    for (const row of list) {
        days.set(row.date, [...(days.get(row.date) ?? []), row]);
    }

    return [...days.entries()]
        .sort(([a], [b]) =>
            order === 'asc' ? a.localeCompare(b) : b.localeCompare(a),
        )
        .map(([date, items]) => ({
            date,
            items,
            total: items.reduce(
                (sum, r) =>
                    sum + (r.kind === 'expense' ? r.expense.amountCents : 0),
                0,
            ),
        }));
}

const spentShare = computed(() =>
    totals.value.projected
        ? (totals.value.spent / totals.value.projected) * 100
        : 0,
);
</script>

<template>
    <div class="space-y-5">
        <div
            class="sticky top-14 z-30 -mx-4 space-y-2 border-b bg-background/95 px-4 py-3 backdrop-blur md:top-16 md:-mx-6 md:px-6"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h1 class="text-xl font-semibold tracking-tight">Finances</h1>
                <MonthSwitcher />
            </div>
            <p class="text-sm tabular-nums">
                <span class="font-semibold">{{ money(totals.spent) }}</span>
                spent ·
                <span class="font-semibold">{{ money(totals.stillDue) }}</span>
                still due ·
                <span class="text-muted-foreground">
                    {{ money(totals.projected) }} projected
                </span>
            </p>
            <div class="flex h-1.5 overflow-hidden rounded-full bg-muted">
                <div class="bg-primary" :style="{ width: `${spentShare}%` }" />
                <div class="flex-1 bg-(--meter-warn-track)" />
            </div>
            <div class="flex gap-1">
                <button
                    v-for="option in ['all', 'bills', 'spending'] as Filter[]"
                    :key="option"
                    type="button"
                    class="rounded-full px-3 py-1 text-xs capitalize"
                    :class="
                        filter === option
                            ? 'bg-lavender font-medium text-lavender-foreground'
                            : 'text-muted-foreground hover:bg-accent'
                    "
                    @click="filter = option"
                >
                    {{ option }}
                </button>
            </div>
        </div>

        <section
            v-if="overdue.length && filter !== 'spending'"
            class="space-y-2 rounded-2xl border border-destructive/40 bg-destructive/5 p-4"
        >
            <h2
                class="flex items-center gap-1.5 text-sm font-semibold text-destructive"
            >
                <CircleAlert class="size-4" /> Needs attention
            </h2>
            <div
                v-for="occurrence in overdue"
                :key="occurrence.key"
                class="flex items-center gap-3"
            >
                <p class="flex-1 text-sm">
                    {{ occurrence.bill.name }}
                    <span class="text-xs text-muted-foreground">
                        · was due {{ formatDay(occurrence.dueOn) }}
                    </span>
                </p>
                <span class="text-sm tabular-nums">{{
                    money(occurrence.amountCents)
                }}</span>
                <Button size="sm" @click="paying = occurrence">Paid</Button>
            </div>
        </section>

        <section v-if="upcomingDays.length" class="space-y-3">
            <h2
                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                Coming up
            </h2>
            <div v-for="day in upcomingDays" :key="day.date" class="flex gap-4">
                <p class="w-24 shrink-0 pt-2 text-xs text-muted-foreground">
                    {{ formatDay(day.date) }}
                </p>
                <ul class="flex-1 space-y-1">
                    <template
                        v-for="row in day.items"
                        :key="
                            row.date +
                            (row.kind === 'expense'
                                ? row.expense.id
                                : row.occurrence.key)
                        "
                    >
                        <li
                            v-if="row.kind === 'occurrence'"
                            class="flex items-center gap-3 rounded-xl border border-dashed px-3 py-2"
                            :class="
                                row.occurrence.status === 'skipped' &&
                                'opacity-50'
                            "
                        >
                            <p class="flex flex-1 items-center gap-1.5 text-sm">
                                <span
                                    :class="
                                        row.occurrence.status === 'skipped' &&
                                        'line-through'
                                    "
                                >
                                    {{ row.occurrence.bill.name }}
                                </span>
                                <Repeat
                                    v-if="row.occurrence.bill.autopay"
                                    class="size-3.5 text-muted-foreground"
                                    aria-label="Autopay"
                                />
                            </p>
                            <span
                                class="text-sm text-muted-foreground tabular-nums"
                            >
                                {{ row.occurrence.bill.variable ? '~' : ''
                                }}{{ money(row.occurrence.amountCents) }}
                            </span>
                            <button
                                type="button"
                                class="text-xs text-muted-foreground hover:text-foreground"
                                @click="toggleSkip(row.occurrence)"
                            >
                                {{
                                    row.occurrence.status === 'skipped'
                                        ? 'Undo'
                                        : 'Skip'
                                }}
                            </button>
                            <Button
                                v-if="
                                    row.occurrence.status !== 'skipped' &&
                                    !row.occurrence.bill.autopay
                                "
                                size="sm"
                                variant="outline"
                                @click="paying = row.occurrence"
                            >
                                Paid
                            </Button>
                        </li>
                    </template>
                </ul>
            </div>
        </section>

        <div class="flex items-center gap-3 text-xs font-semibold text-primary">
            <span class="h-px flex-1 bg-primary/40" /> Today ·
            {{ formatDay(today) }}
            <span class="h-px flex-1 bg-primary/40" />
        </div>

        <section class="space-y-4">
            <p
                v-if="pastDays.length === 0"
                class="py-8 text-center text-sm text-muted-foreground"
            >
                Nothing here yet.
            </p>
            <div v-for="day in pastDays" :key="day.date" class="flex gap-4">
                <div class="w-24 shrink-0 pt-2">
                    <p class="text-xs text-muted-foreground">
                        {{ formatDay(day.date) }}
                    </p>
                    <p
                        v-if="day.total"
                        class="text-xs font-medium tabular-nums"
                    >
                        {{ money(day.total) }}
                    </p>
                </div>
                <ul class="flex-1 divide-y rounded-xl border bg-card">
                    <template
                        v-for="row in day.items"
                        :key="
                            row.kind === 'expense'
                                ? row.expense.id
                                : row.occurrence.key
                        "
                    >
                        <li
                            v-if="row.kind === 'expense'"
                            class="group flex items-center gap-3 px-3 py-2"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm">
                                    {{
                                        row.expense.note ||
                                        categoryName(row.expense.categoryId)
                                    }}
                                    <span
                                        v-if="row.expense.billId"
                                        class="ml-1 rounded-full bg-secondary px-1.5 py-0.5 text-[10px] text-secondary-foreground"
                                        >bill</span
                                    >
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ categoryName(row.expense.categoryId) }} ·
                                    {{
                                        paymentMethodName(
                                            row.expense.paymentMethodId,
                                        )
                                    }}
                                </p>
                            </div>
                            <span class="text-sm tabular-nums">{{
                                money(row.expense.amountCents)
                            }}</span>
                            <button
                                type="button"
                                class="text-xs text-muted-foreground opacity-0 group-hover:opacity-100 hover:text-destructive focus:opacity-100"
                                @click="deleteExpense(row.expense)"
                            >
                                Delete
                            </button>
                        </li>
                        <li
                            v-else
                            class="flex items-center gap-3 px-3 py-2 opacity-50"
                        >
                            <p class="flex-1 text-sm line-through">
                                {{ row.occurrence.bill.name }}
                            </p>
                            <button
                                type="button"
                                class="text-xs text-muted-foreground hover:text-foreground"
                                @click="toggleSkip(row.occurrence)"
                            >
                                Undo skip
                            </button>
                        </li>
                    </template>
                </ul>
            </div>
        </section>

        <button
            type="button"
            class="fixed right-5 bottom-[calc(8.5rem+env(safe-area-inset-bottom))] z-40 flex size-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg hover:bg-primary/90 md:right-8 md:bottom-20"
            aria-label="Add expense"
            @click="addingExpense = true"
        >
            <Plus class="size-6" />
        </button>
    </div>
</template>
