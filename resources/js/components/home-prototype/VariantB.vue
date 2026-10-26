<script setup lang="ts">
/**
 * PROTOTYPE variant B — "Money first": the month's spending is the hero.
 * Budget meters, stat tiles and a six-month trend lead; logging an expense
 * is the primary action. Bills and chores are secondary lists.
 */
import { ChartColumn, Plus, Table2, TrendingDown, X } from '@lucide/vue';
import { computed, nextTick, reactive, ref, watch } from 'vue';
import type { Expense } from './fixtures';
import {
    agenda,
    budget,
    incomeThisMonthCents,
    monthlySpendCents,
    recentExpenses,
    utilitiesLastYearMonthlyCents,
    utilitiesMonthlyCents,
    percentChange,
} from './fixtures';
import {
    compactMoney,
    daysLeftInMonth,
    money,
    moneyWhole,
    monthName,
    parseCents,
    relativeDue,
} from './format';
import Meter from './Meter.vue';

const categories = ref(budget.map((category) => ({ ...category })));
const expenses = ref<Expense[]>(
    recentExpenses.map((expense) => ({ ...expense })),
);

const spentCents = computed(() =>
    categories.value.reduce(
        (total, category) => total + category.spentCents,
        0,
    ),
);
const budgetCents = computed(() =>
    categories.value.reduce(
        (total, category) => total + category.budgetCents,
        0,
    ),
);

const upcomingBills = agenda.filter(
    (item) =>
        ['bill', 'subscription', 'utility'].includes(item.kind) &&
        item.dueInDays < 14,
);
const billsDueCents = upcomingBills.reduce(
    (total, item) => total + (item.amountCents ?? 0),
    0,
);
const utilitiesChange = percentChange(
    utilitiesMonthlyCents,
    utilitiesLastYearMonthlyCents,
);

// Six-month column chart: single series (slot 1), clean ticks, value on the latest cap.
const months = monthlySpendCents.map((cents, i) => ({
    label: monthName(i - monthlySpendCents.length, 'short'),
    cents,
}));
const axisMaxCents = 600000;
const ticks = [600000, 400000, 200000, 0];
const hovered = ref<number | null>(null);
const showTable = ref(false);
const barPercent = (cents: number): number => (cents / axisMaxCents) * 100;

// Quick-add: in memory only.
const adding = ref(false);
const draft = reactive({ amount: '', category: 'Groceries', description: '' });
const draftError = ref('');
const amountInput = ref<HTMLInputElement | null>(null);

watch(adding, async (isOpen) => {
    if (isOpen) {
        await nextTick();
        amountInput.value?.focus();
    }
});

function saveExpense(): void {
    const cents = parseCents(draft.amount);

    if (cents === null || cents === 0) {
        draftError.value = 'Enter an amount like 42.50';

        return;
    }

    expenses.value = [
        {
            id: `new-${Date.now()}`,
            description: draft.description || draft.category,
            category: draft.category,
            amountCents: cents,
            daysAgo: 0,
        },
        ...expenses.value,
    ];
    categories.value = categories.value.map((category) =>
        category.name === draft.category
            ? { ...category, spentCents: category.spentCents + cents }
            : category,
    );

    Object.assign(draft, { amount: '', description: '' });
    draftError.value = '';
    adding.value = false;
}
</script>

<template>
    <div class="space-y-6">
        <section
            class="flex flex-col gap-5 rounded-3xl border border-border bg-card p-6 md:flex-row md:items-end md:justify-between md:p-8"
        >
            <div class="min-w-0 flex-1 space-y-3">
                <p class="text-sm font-medium text-muted-foreground">
                    Spent in {{ monthName() }}
                </p>
                <p class="text-5xl font-semibold tracking-tight md:text-6xl">
                    {{ money(spentCents) }}
                </p>
                <p class="text-muted-foreground">
                    of {{ moneyWhole(budgetCents) }} budget ·
                    {{ daysLeftInMonth() }} days left
                </p>
                <div class="max-w-md pt-1">
                    <Meter
                        :value-cents="spentCents"
                        :limit-cents="budgetCents"
                        show-status
                    />
                </div>
            </div>
            <button
                type="button"
                class="hidden items-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:opacity-90 md:inline-flex"
                @click="adding = true"
            >
                <Plus class="size-4" /> Log expense
            </button>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-2xl border border-border bg-card p-4">
                <p class="text-sm text-muted-foreground">
                    Bills due in 14 days
                </p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ moneyWhole(billsDueCents) }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ upcomingBills.length }} bills
                </p>
            </div>
            <div class="rounded-2xl border border-border bg-card p-4">
                <p class="text-sm text-muted-foreground">Income this month</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ moneyWhole(incomeThisMonthCents) }}
                </p>
                <p class="text-xs text-muted-foreground">2 paycheques</p>
            </div>
            <div class="rounded-2xl border border-border bg-card p-4">
                <p class="text-sm text-muted-foreground">Left to budget</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ moneyWhole(Math.max(budgetCents - spentCents, 0)) }}
                </p>
                <p class="text-xs text-muted-foreground">
                    for {{ daysLeftInMonth() }} days
                </p>
            </div>
            <div class="rounded-2xl border border-border bg-card p-4">
                <p class="text-sm text-muted-foreground">Utilities</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ moneyWhole(utilitiesMonthlyCents) }}
                </p>
                <p
                    class="flex items-center gap-1 text-xs text-muted-foreground"
                >
                    <TrendingDown class="size-3.5 text-(--meter-ok)" />
                    {{ utilitiesChange }}% vs last October
                </p>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-5">
            <section
                class="rounded-2xl border border-border bg-card p-5 lg:col-span-3"
            >
                <h2 class="mb-4 font-semibold">Budget by category</h2>
                <ul class="space-y-4">
                    <li v-for="category in categories" :key="category.name">
                        <div
                            class="mb-1.5 flex items-baseline justify-between gap-3 text-sm"
                        >
                            <span class="font-medium">{{ category.name }}</span>
                            <span class="text-muted-foreground tabular-nums"
                                >{{ moneyWhole(category.spentCents) }} of
                                {{ moneyWhole(category.budgetCents) }}</span
                            >
                        </div>
                        <Meter
                            :value-cents="category.spentCents"
                            :limit-cents="category.budgetCents"
                            show-status
                        />
                    </li>
                </ul>
            </section>

            <section
                class="rounded-2xl border border-border bg-card p-5 lg:col-span-2"
            >
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">Monthly spending</h2>
                        <p class="text-sm text-muted-foreground">
                            Last six complete months
                        </p>
                    </div>
                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-full border border-border px-2.5 py-1 text-xs hover:bg-accent"
                        :aria-pressed="showTable"
                        @click="showTable = !showTable"
                    >
                        <component
                            :is="showTable ? ChartColumn : Table2"
                            class="size-3.5"
                        />
                        {{ showTable ? 'Chart' : 'Table' }}
                    </button>
                </div>

                <table v-if="showTable" class="w-full text-sm">
                    <tbody>
                        <tr
                            v-for="month in months"
                            :key="month.label"
                            class="border-b border-border last:border-0"
                        >
                            <td class="py-2 text-muted-foreground">
                                {{ month.label }}
                            </td>
                            <td
                                class="py-2 text-right font-medium tabular-nums"
                            >
                                {{ money(month.cents) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <figure v-else class="flex gap-3">
                    <div
                        class="flex h-44 flex-col justify-between pb-6 text-right text-[11px] text-muted-foreground tabular-nums"
                    >
                        <span
                            v-for="tick in ticks"
                            :key="tick"
                            class="-translate-y-1/2 leading-none"
                            >{{ compactMoney(tick) }}</span
                        >
                    </div>
                    <div class="relative flex-1">
                        <div
                            class="pointer-events-none absolute inset-x-0 top-0 h-38"
                        >
                            <div
                                v-for="tick in ticks"
                                :key="tick"
                                class="absolute inset-x-0 border-t border-border"
                                :style="{
                                    top: `${(1 - tick / axisMaxCents) * 100}%`,
                                }"
                            />
                        </div>
                        <div
                            class="relative flex h-44 items-end justify-around pb-6"
                        >
                            <button
                                v-for="(month, i) in months"
                                :key="month.label"
                                type="button"
                                class="group relative flex h-38 w-full flex-col items-center justify-end focus:outline-none"
                                :aria-label="`${month.label}: ${money(month.cents)}`"
                                @mouseenter="hovered = i"
                                @mouseleave="hovered = null"
                                @focus="hovered = i"
                                @blur="hovered = null"
                            >
                                <span
                                    v-if="hovered === i"
                                    class="absolute left-1/2 z-10 -translate-x-1/2 rounded-lg border border-border bg-popover px-2.5 py-1.5 text-left whitespace-nowrap shadow-md"
                                    :style="{
                                        bottom: `calc(${barPercent(month.cents)}% + 6px)`,
                                    }"
                                    role="tooltip"
                                >
                                    <span
                                        class="block text-sm font-semibold tabular-nums"
                                        >{{ money(month.cents) }}</span
                                    >
                                    <span
                                        class="block text-xs text-muted-foreground"
                                        >{{ month.label }}</span
                                    >
                                </span>
                                <span
                                    v-else-if="i === months.length - 1"
                                    class="absolute left-1/2 -translate-x-1/2 text-[11px] font-medium"
                                    :style="{
                                        bottom: `calc(${barPercent(month.cents)}% + 4px)`,
                                    }"
                                >
                                    {{ compactMoney(month.cents) }}
                                </span>
                                <span
                                    class="w-5 rounded-t-[4px] bg-chart-1 transition-opacity group-focus-visible:ring-2 group-focus-visible:ring-ring"
                                    :class="
                                        hovered !== null && hovered !== i
                                            ? 'opacity-50'
                                            : ''
                                    "
                                    :style="{
                                        height: `${barPercent(month.cents)}%`,
                                    }"
                                />
                                <span
                                    class="absolute -bottom-6 text-[11px] text-muted-foreground"
                                    >{{ month.label }}</span
                                >
                            </button>
                        </div>
                    </div>
                </figure>
            </section>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <section class="rounded-2xl border border-border bg-card p-5">
                <h2 class="mb-3 font-semibold">Coming up</h2>
                <ul class="divide-y divide-border">
                    <li
                        v-for="bill in upcomingBills"
                        :key="bill.id"
                        class="flex items-center gap-3 py-2.5 text-sm"
                    >
                        <component
                            :is="bill.icon"
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="flex-1 truncate">{{ bill.title }}</span>
                        <span class="text-muted-foreground">{{
                            relativeDue(bill.dueInDays)
                        }}</span>
                        <span
                            class="w-20 text-right font-medium tabular-nums"
                            >{{ money(bill.amountCents ?? 0) }}</span
                        >
                    </li>
                </ul>
            </section>

            <section class="rounded-2xl border border-border bg-card p-5">
                <h2 class="mb-3 font-semibold">Recent expenses</h2>
                <ul class="divide-y divide-border">
                    <li
                        v-for="expense in expenses"
                        :key="expense.id"
                        class="flex items-center gap-3 py-2.5 text-sm"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate">{{ expense.description }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ expense.category }} ·
                                {{
                                    expense.daysAgo === 0
                                        ? 'Today'
                                        : `${expense.daysAgo}d ago`
                                }}
                            </p>
                        </div>
                        <span class="font-medium tabular-nums">{{
                            money(expense.amountCents)
                        }}</span>
                    </li>
                </ul>
            </section>
        </div>

        <button
            type="button"
            class="fixed right-4 bottom-[calc(8.5rem+env(safe-area-inset-bottom))] z-30 flex size-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg md:hidden"
            aria-label="Log expense"
            @click="adding = true"
        >
            <Plus class="size-6" />
        </button>

        <div
            v-if="adding"
            class="fixed inset-0 z-60 flex items-end justify-center bg-black/30 md:items-center"
            @click.self="adding = false"
        >
            <form
                class="w-full max-w-md space-y-4 rounded-t-3xl border border-border bg-card p-6 pb-[calc(1.5rem+env(safe-area-inset-bottom))] shadow-xl md:rounded-3xl md:pb-6"
                @submit.prevent="saveExpense"
            >
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Log an expense</h2>
                    <button
                        type="button"
                        class="rounded-full p-1.5 hover:bg-accent"
                        aria-label="Close"
                        @click="adding = false"
                    >
                        <X class="size-4" />
                    </button>
                </div>
                <label class="block space-y-1.5">
                    <span class="text-sm font-medium">Amount</span>
                    <input
                        ref="amountInput"
                        v-model="draft.amount"
                        inputmode="decimal"
                        placeholder="0.00"
                        class="w-full rounded-xl border border-input bg-card px-3 py-2.5 text-2xl font-semibold tabular-nums focus:ring-2 focus:ring-ring focus:outline-none"
                    />
                    <span v-if="draftError" class="text-sm text-destructive">{{
                        draftError
                    }}</span>
                </label>
                <div class="space-y-1.5">
                    <span class="text-sm font-medium">Category</span>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="category in categories"
                            :key="category.name"
                            type="button"
                            class="rounded-full border px-3 py-1 text-sm"
                            :class="
                                draft.category === category.name
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-border hover:bg-accent'
                            "
                            @click="draft.category = category.name"
                        >
                            {{ category.name }}
                        </button>
                    </div>
                </div>
                <label class="block space-y-1.5">
                    <span class="text-sm font-medium"
                        >Note
                        <span class="font-normal text-muted-foreground"
                            >(optional)</span
                        ></span
                    >
                    <input
                        v-model="draft.description"
                        placeholder="Where or what"
                        class="w-full rounded-xl border border-input bg-card px-3 py-2 focus:ring-2 focus:ring-ring focus:outline-none"
                    />
                </label>
                <button
                    type="submit"
                    class="w-full rounded-full bg-primary py-2.5 font-medium text-primary-foreground hover:opacity-90"
                >
                    Save
                </button>
            </form>
        </div>
    </div>
</template>
