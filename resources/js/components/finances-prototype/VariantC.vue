<script setup lang="ts">
/**
 * PROTOTYPE variant C — "Categories": inline quick-add bar on top, a strip of
 * the next unpaid bills, then the month as category rows (spent solid, still
 * due light). Tap a category to open its bills and expenses in place.
 */
import { ChevronDown, Repeat } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDay, money, parseCents } from './format';
import MonthSwitcher from './MonthSwitcher.vue';
import {
    addExpense,
    byCategory,
    categories,
    daysBetween,
    deleteExpense,
    monthExpenses,
    monthOccurrences,
    paying,
    paymentMethodName,
    today,
    toggleSkip,
    totals,
    unpaid,
} from './store';

const quick = ref({
    amount: '',
    categoryId: 'groceries',
    note: '',
    paidOn: today,
});
const quickError = ref('');
const showDate = ref(false);

function submitQuick(): void {
    const cents = parseCents(quick.value.amount);

    if (cents === null || cents === 0) {
        quickError.value = 'Amount like 19.99';

        return;
    }

    addExpense({
        amountCents: cents,
        categoryId: quick.value.categoryId,
        note: quick.value.note,
        paidOn: quick.value.paidOn,
        paymentMethodId: null,
    });
    quick.value = {
        amount: '',
        categoryId: quick.value.categoryId,
        note: '',
        paidOn: today,
    };
    quickError.value = '';
    showDate.value = false;
}

const open = ref<string | null>(null);
const largest = computed(() =>
    Math.max(1, ...byCategory.value.map((r) => r.spent + r.due)),
);

const nextUp = computed(() => unpaid.value.slice(0, 6));

const dueLabel = (dueOn: string): string => {
    const days = daysBetween(today, dueOn);

    return days < 0
        ? `${-days}d overdue`
        : days === 0
          ? 'Today'
          : `in ${days}d`;
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <MonthSwitcher class="-ml-1.5" />
                <p
                    class="mt-1 text-3xl font-semibold tracking-tight tabular-nums"
                >
                    {{ money(totals.projected) }}
                </p>
                <p class="text-sm text-muted-foreground tabular-nums">
                    {{ money(totals.spent) }} spent +
                    {{ money(totals.stillDue) }} still due
                </p>
            </div>
        </div>

        <form
            class="flex flex-wrap items-center gap-2 rounded-2xl border bg-card p-2"
            @submit.prevent="submitQuick"
        >
            <Input
                v-model="quick.amount"
                class="w-28"
                inputmode="decimal"
                placeholder="$0.00"
                aria-label="Amount"
                :aria-invalid="quickError !== ''"
            />
            <select
                v-model="quick.categoryId"
                class="h-9 rounded-md border border-input bg-transparent px-2 text-sm dark:bg-input/30"
                aria-label="Category"
            >
                <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="category.id"
                >
                    {{ category.name }}
                </option>
            </select>
            <Input
                v-model="quick.note"
                class="min-w-32 flex-1"
                placeholder="What was it? (optional)"
                aria-label="Note"
            />
            <Input
                v-if="showDate"
                v-model="quick.paidOn"
                type="date"
                class="w-40"
                aria-label="Date"
            />
            <button
                v-else
                type="button"
                class="px-1 text-xs text-muted-foreground hover:text-foreground"
                @click="showDate = true"
            >
                Today ▾
            </button>
            <Button type="submit" size="sm">Add</Button>
            <p v-if="quickError" class="w-full px-1 text-xs text-destructive">
                {{ quickError }}
            </p>
        </form>

        <section v-if="nextUp.length" class="space-y-2">
            <h2 class="text-sm font-semibold">Next bills</h2>
            <div
                class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 md:mx-0 md:px-0"
            >
                <div
                    v-for="occurrence in nextUp"
                    :key="occurrence.key"
                    class="w-40 shrink-0 space-y-2 rounded-2xl border bg-card p-3"
                    :class="
                        occurrence.status === 'overdue' &&
                        'border-destructive/50'
                    "
                >
                    <p
                        class="flex items-center gap-1 truncate text-sm font-medium"
                    >
                        {{ occurrence.bill.name }}
                        <Repeat
                            v-if="occurrence.bill.autopay"
                            class="size-3.5 text-muted-foreground"
                            aria-label="Autopay"
                        />
                    </p>
                    <p class="text-lg font-semibold tabular-nums">
                        {{ occurrence.bill.variable ? '~' : ''
                        }}{{ money(occurrence.amountCents) }}
                    </p>
                    <p
                        class="text-xs"
                        :class="
                            occurrence.status === 'overdue'
                                ? 'font-medium text-destructive'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ dueLabel(occurrence.dueOn) }}
                    </p>
                    <Button
                        size="sm"
                        variant="outline"
                        class="w-full"
                        @click="paying = occurrence"
                    >
                        Paid
                    </Button>
                </div>
            </div>
        </section>

        <section class="divide-y rounded-2xl border bg-card">
            <div v-for="row in byCategory" :key="row.id">
                <button
                    type="button"
                    class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-accent/50"
                    :aria-expanded="open === row.id"
                    @click="open = open === row.id ? null : row.id"
                >
                    <div class="min-w-0 flex-1 space-y-1.5">
                        <div
                            class="flex items-baseline justify-between gap-2 text-sm"
                        >
                            <span class="font-medium">{{ row.name }}</span>
                            <span class="tabular-nums">
                                {{ money(row.spent) }}
                                <span
                                    v-if="row.due"
                                    class="text-muted-foreground"
                                >
                                    + {{ money(row.due) }} due
                                </span>
                            </span>
                        </div>
                        <div
                            class="flex h-2 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="bg-chart-1"
                                :style="{
                                    width: `${(row.spent / largest) * 100}%`,
                                }"
                            />
                            <div
                                class="bg-chart-1/30"
                                :style="{
                                    width: `${(row.due / largest) * 100}%`,
                                }"
                            />
                        </div>
                    </div>
                    <ChevronDown
                        class="size-4 shrink-0 text-muted-foreground transition-transform"
                        :class="open === row.id && 'rotate-180'"
                    />
                </button>

                <ul
                    v-if="open === row.id"
                    class="space-y-1 bg-muted/40 px-4 py-2 text-sm"
                >
                    <li
                        v-for="occurrence in monthOccurrences.filter(
                            (o) =>
                                o.bill.categoryId === row.id &&
                                o.status !== 'paid',
                        )"
                        :key="occurrence.key"
                        class="flex items-center gap-3 py-1"
                    >
                        <span
                            class="flex-1"
                            :class="
                                occurrence.status === 'skipped' &&
                                'line-through opacity-50'
                            "
                        >
                            {{ occurrence.bill.name }}
                            <span class="text-xs text-muted-foreground"
                                >· due {{ formatDay(occurrence.dueOn) }}</span
                            >
                        </span>
                        <span class="text-muted-foreground tabular-nums">{{
                            money(occurrence.amountCents)
                        }}</span>
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
                    </li>
                    <li
                        v-for="expense in monthExpenses.filter(
                            (e) => e.categoryId === row.id,
                        )"
                        :key="expense.id"
                        class="group flex items-center gap-3 py-1"
                    >
                        <span class="min-w-0 flex-1 truncate">
                            {{ expense.note || row.name }}
                            <span class="text-xs text-muted-foreground">
                                · {{ formatDay(expense.paidOn) }} ·
                                {{ paymentMethodName(expense.paymentMethodId) }}
                            </span>
                        </span>
                        <span class="tabular-nums">{{
                            money(expense.amountCents)
                        }}</span>
                        <button
                            type="button"
                            class="text-xs text-muted-foreground opacity-0 group-hover:opacity-100 hover:text-destructive focus:opacity-100"
                            @click="deleteExpense(expense)"
                        >
                            Delete
                        </button>
                    </li>
                </ul>
            </div>
            <p
                v-if="byCategory.length === 0"
                class="px-4 py-8 text-center text-sm text-muted-foreground"
            >
                Nothing spent or due this month.
            </p>
        </section>
    </div>
</template>
