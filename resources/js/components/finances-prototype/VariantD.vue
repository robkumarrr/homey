<script setup lang="ts">
/**
 * PROTOTYPE variant D — "Ledger v2", from feedback on A: title and Add on one
 * row (Add stays right-aligned on phones), a fixed-width month switcher,
 * one Add dialog for expenses and bills, undoable deletes, project payments
 * from contractors shown alongside bills, and an "All bills" list.
 */
import { Check, Hammer, Plus, Repeat, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { formatDay, money } from './format';
import MonthSwitcher from './MonthSwitcher.vue';
import {
    billFor,
    bills,
    byCategory,
    categoryName,
    contactLabel,
    daysBetween,
    deleteExpense,
    lastPaidCents,
    monthExpenses,
    monthOccurrences,
    openAdd,
    paying,
    paymentMethodName,
    repeatLabels,
    state,
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

const recurringBills = computed(() =>
    bills
        .filter((bill) => !bill.project)
        .sort((a, b) => a.name.localeCompare(b.name)),
);

const project = computed(() => {
    const payments = bills.filter((bill) => bill.project);
    const paid = payments.filter((bill) =>
        state.expenses.some((e) => e.billId === bill.id),
    );
    const next = payments.find((bill) => !paid.includes(bill));

    return {
        ...payments[0].project!,
        contractCents: payments.reduce((sum, b) => sum + b.amountCents, 0),
        paidCents: paid.reduce((sum, b) => sum + b.amountCents, 0),
        next,
    };
});

const showAllBills = ref(false);
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

        <div class="grid gap-6 md:grid-cols-2">
            <section class="rounded-2xl border bg-card">
                <div
                    class="flex items-center justify-between border-b px-4 py-3"
                >
                    <h2 class="text-sm font-semibold">Due this month</h2>
                    <button
                        type="button"
                        class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                        @click="openAdd('bill')"
                    >
                        <Plus class="size-3.5" /> Bill
                    </button>
                </div>
                <p
                    v-if="monthOccurrences.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    No bills due this month.
                </p>
                <ul class="divide-y">
                    <li
                        v-for="occurrence in monthOccurrences"
                        :key="occurrence.key"
                        class="flex items-center gap-3 px-4 py-3"
                        :class="occurrence.status === 'skipped' && 'opacity-50'"
                    >
                        <Hammer
                            v-if="occurrence.bill.project"
                            class="size-4 shrink-0 text-muted-foreground"
                            aria-label="Project payment"
                        />
                        <div class="min-w-0 flex-1">
                            <p
                                class="flex items-center gap-1.5 text-sm font-medium"
                            >
                                <span
                                    class="truncate"
                                    :class="
                                        occurrence.status === 'skipped' &&
                                        'line-through'
                                    "
                                    >{{ occurrence.bill.name }}</span
                                >
                                <Repeat
                                    v-if="occurrence.bill.autopay"
                                    class="size-3.5 shrink-0 text-muted-foreground"
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
                                <template v-if="occurrence.bill.project">
                                    · {{ occurrence.bill.project.contractor }}
                                </template>
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
                </ul>
            </section>

            <section class="rounded-2xl border bg-card">
                <div
                    class="flex items-center justify-between border-b px-4 py-3"
                >
                    <h2 class="text-sm font-semibold">
                        Expenses
                        <span class="font-normal text-muted-foreground">
                            · {{ monthExpenses.length }}
                        </span>
                    </h2>
                    <button
                        type="button"
                        class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                        @click="openAdd('expense')"
                    >
                        <Plus class="size-3.5" /> Expense
                    </button>
                </div>
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
                            <p class="flex items-center gap-1.5 text-sm">
                                <Hammer
                                    v-if="billFor(expense)?.project"
                                    class="size-3.5 shrink-0 text-muted-foreground"
                                    aria-label="Project payment"
                                />
                                <span class="truncate">
                                    {{
                                        expense.note ||
                                        categoryName(expense.categoryId)
                                    }}
                                </span>
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

        <section
            class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-2xl border bg-card px-4 py-3"
        >
            <Hammer class="size-4 text-muted-foreground" />
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium">{{ project.name }}</p>
                <p class="text-xs text-muted-foreground">
                    {{ project.contractor }} · {{ money(project.paidCents) }} of
                    {{ money(project.contractCents) }} paid
                    <template v-if="project.next">
                        · next: {{ money(project.next.amountCents) }} on
                        {{ formatDay(project.next.firstDueOn) }}
                    </template>
                </p>
            </div>
            <span class="text-xs text-muted-foreground">
                Project payments show up here and in Projects (#6)
            </span>
        </section>

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

        <section class="rounded-2xl border bg-card">
            <button
                type="button"
                class="flex w-full items-center justify-between px-4 py-3 text-sm font-semibold"
                :aria-expanded="showAllBills"
                @click="showAllBills = !showAllBills"
            >
                All bills · {{ recurringBills.length }}
                <span class="text-xs font-normal text-muted-foreground">
                    {{ showAllBills ? 'Hide' : 'Show' }}
                </span>
            </button>
            <ul v-if="showAllBills" class="divide-y border-t">
                <li
                    v-for="bill in recurringBills"
                    :key="bill.id"
                    class="flex items-center gap-3 px-4 py-2.5"
                >
                    <div class="min-w-0 flex-1">
                        <p class="flex items-center gap-1.5 text-sm">
                            {{ bill.name }}
                            <Repeat
                                v-if="bill.autopay"
                                class="size-3.5 text-muted-foreground"
                                aria-label="Autopay"
                            />
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ repeatLabels[bill.everyMonths] }} ·
                            {{ categoryName(bill.categoryId) }} ·
                            {{ paymentMethodName(bill.paymentMethodId) }}
                            <template v-if="contactLabel(bill.contactId)">
                                · {{ contactLabel(bill.contactId) }}
                            </template>
                        </p>
                    </div>
                    <span class="text-sm tabular-nums">
                        {{ bill.variable ? '~' : ''
                        }}{{
                            money(
                                bill.variable
                                    ? lastPaidCents(bill)
                                    : bill.amountCents,
                            )
                        }}
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
