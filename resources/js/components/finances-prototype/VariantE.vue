<script setup lang="ts">
/**
 * PROTOTYPE variant E — "Calendar": the Ledger v2 header and totals over a
 * month grid. Bills and project payments sit on their due dates; everyday
 * spending shows as a per-day total. Pick a day to act on it below the grid.
 * Hand-rolled for the prototype; the real one would likely use Schedule-X.
 */
import { Check, Hammer, Plus, Repeat, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { compactMoney, formatDay, money } from './format';
import MonthSwitcher from './MonthSwitcher.vue';
import {
    billFor,
    categoryName,
    deleteExpense,
    iso,
    monthExpenses,
    monthOccurrences,
    openAdd,
    paying,
    paymentMethodName,
    selectedMonth,
    today,
    toggleSkip,
    totals,
} from './store';

const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const cells = computed(() => {
    const start = selectedMonth.value;
    const leading = start.getDay();
    const daysInMonth = new Date(
        start.getFullYear(),
        start.getMonth() + 1,
        0,
    ).getDate();

    return [
        ...Array.from({ length: leading }, () => null),
        ...Array.from({ length: daysInMonth }, (_, i) => {
            const date = iso(
                new Date(start.getFullYear(), start.getMonth(), i + 1),
            );
            const occurrences = monthOccurrences.value.filter(
                (o) => o.dueOn === date,
            );
            const spent = monthExpenses.value
                .filter((e) => e.paidOn === date && e.billId === null)
                .reduce((sum, e) => sum + e.amountCents, 0);

            return { date, day: i + 1, occurrences, spent };
        }),
    ];
});

const selected = ref(today);

watch(
    selectedMonth,
    (start) => {
        const prefix = iso(start).slice(0, 7);
        selected.value = today.startsWith(prefix) ? today : `${prefix}-01`;
    },
    { immediate: true },
);

const dayOccurrences = computed(() =>
    monthOccurrences.value.filter((o) => o.dueOn === selected.value),
);
const dayExpenses = computed(() =>
    monthExpenses.value.filter((e) => e.paidOn === selected.value),
);

const chipClass = (status: string): string =>
    ({
        paid: 'bg-(--meter-ok-track) text-foreground',
        overdue: 'bg-destructive/15 text-destructive font-medium',
        skipped: 'bg-muted text-muted-foreground line-through',
        due: 'border border-primary/40 text-foreground',
    })[status] ?? '';
</script>

<template>
    <div class="space-y-6">
        <div class="space-y-2">
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-2xl font-semibold tracking-tight md:text-3xl">
                    Finances
                </h1>
                <Button @click="openAdd('expense')"> <Plus /> Add </Button>
            </div>
            <MonthSwitcher class="-ml-2.5" />
        </div>

        <section class="grid grid-cols-3 gap-3">
            <div class="rounded-2xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Spent so far</p>
                <p class="mt-1 text-lg font-semibold tabular-nums md:text-2xl">
                    {{ money(totals.spent) }}
                </p>
            </div>
            <div class="rounded-2xl border bg-card p-4">
                <p class="text-xs text-muted-foreground">Still due</p>
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

        <section class="overflow-hidden rounded-2xl border bg-card">
            <div
                class="grid grid-cols-7 border-b text-center text-xs text-muted-foreground"
            >
                <div v-for="weekday in weekdays" :key="weekday" class="py-2">
                    {{ weekday }}
                </div>
            </div>
            <div class="grid grid-cols-7">
                <template v-for="(cell, index) in cells" :key="index">
                    <div v-if="!cell" class="border-r border-b bg-muted/30" />
                    <button
                        v-else
                        type="button"
                        class="flex min-h-16 flex-col items-stretch gap-1 border-r border-b p-1 text-left hover:bg-accent/50 md:min-h-28 md:p-1.5"
                        :class="
                            cell.date === selected &&
                            'bg-accent ring-2 ring-primary ring-inset'
                        "
                        :aria-pressed="cell.date === selected"
                        :aria-label="formatDay(cell.date)"
                        @click="selected = cell.date"
                    >
                        <span
                            class="flex size-6 items-center justify-center rounded-full text-xs"
                            :class="
                                cell.date === today
                                    ? 'bg-primary font-semibold text-primary-foreground'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ cell.day }}
                        </span>

                        <!-- Phones: dots. Larger screens: chips. -->
                        <span class="flex flex-wrap gap-0.5 md:hidden">
                            <span
                                v-for="occurrence in cell.occurrences"
                                :key="occurrence.key"
                                class="size-1.5 rounded-full"
                                :class="{
                                    'bg-(--meter-ok)':
                                        occurrence.status === 'paid',
                                    'bg-destructive':
                                        occurrence.status === 'overdue',
                                    'bg-primary': occurrence.status === 'due',
                                    'bg-muted-foreground/40':
                                        occurrence.status === 'skipped',
                                }"
                            />
                        </span>
                        <span
                            v-for="occurrence in cell.occurrences.slice(0, 3)"
                            :key="occurrence.key"
                            class="hidden truncate rounded px-1 py-0.5 text-[11px] md:block"
                            :class="chipClass(occurrence.status)"
                        >
                            {{ occurrence.bill.name }}
                            {{ compactMoney(occurrence.amountCents) }}
                        </span>
                        <span
                            v-if="cell.occurrences.length > 3"
                            class="hidden text-[11px] text-muted-foreground md:block"
                        >
                            +{{ cell.occurrences.length - 3 }} more
                        </span>
                        <span
                            v-if="cell.spent"
                            class="mt-auto text-[10px] text-muted-foreground tabular-nums md:text-[11px]"
                        >
                            {{ compactMoney(cell.spent)
                            }}<span class="hidden md:inline"> spent</span>
                        </span>
                    </button>
                </template>
            </div>
        </section>

        <section class="rounded-2xl border bg-card">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <h2 class="text-sm font-semibold">{{ formatDay(selected) }}</h2>
                <span class="text-xs text-muted-foreground">
                    Bills on their due date · spending on the day it was paid
                </span>
            </div>
            <p
                v-if="dayOccurrences.length === 0 && dayExpenses.length === 0"
                class="px-4 py-6 text-center text-sm text-muted-foreground"
            >
                Nothing on this day.
            </p>
            <ul class="divide-y">
                <li
                    v-for="occurrence in dayOccurrences"
                    :key="occurrence.key"
                    class="flex items-center gap-3 px-4 py-3"
                >
                    <Hammer
                        v-if="occurrence.bill.project"
                        class="size-4 shrink-0 text-muted-foreground"
                    />
                    <div class="min-w-0 flex-1">
                        <p
                            class="flex items-center gap-1.5 text-sm font-medium"
                        >
                            <span
                                :class="
                                    occurrence.status === 'skipped' &&
                                    'line-through opacity-50'
                                "
                            >
                                {{ occurrence.bill.name }}
                            </span>
                            <Repeat
                                v-if="occurrence.bill.autopay"
                                class="size-3.5 text-muted-foreground"
                                aria-label="Autopay"
                            />
                        </p>
                        <p class="text-xs text-muted-foreground capitalize">
                            {{ occurrence.status }}
                            <template v-if="occurrence.bill.project">
                                · {{ occurrence.bill.project.contractor }}
                            </template>
                        </p>
                    </div>
                    <span class="text-sm tabular-nums">{{
                        money(occurrence.amountCents)
                    }}</span>
                    <Check
                        v-if="occurrence.status === 'paid'"
                        class="size-4 text-(--meter-ok)"
                        aria-label="Paid"
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
                <li
                    v-for="expense in dayExpenses"
                    :key="expense.id"
                    class="flex items-center gap-3 px-4 py-2.5"
                >
                    <Hammer
                        v-if="billFor(expense)?.project"
                        class="size-4 shrink-0 text-muted-foreground"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm">
                            {{
                                expense.note || categoryName(expense.categoryId)
                            }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ categoryName(expense.categoryId) }} ·
                            {{ paymentMethodName(expense.paymentMethodId) }}
                        </p>
                    </div>
                    <span class="text-sm tabular-nums">{{
                        money(expense.amountCents)
                    }}</span>
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-destructive"
                        aria-label="Delete expense"
                        @click="deleteExpense(expense)"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </li>
            </ul>
        </section>
    </div>
</template>
