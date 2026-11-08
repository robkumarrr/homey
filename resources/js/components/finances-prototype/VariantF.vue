<script setup lang="ts">
/**
 * PROTOTYPE variant F — "Ledger v4": E plus feedback. Every change (pay,
 * skip, delete, edit) confirms with one kind of toast that can undo it; paid
 * and skipped bills can be reversed later from a ⋯ menu; expenses get edit
 * and delete (with an "are you sure?"), and anything removed lands in
 * "Recently deleted" in case the toast was missed. Dialogs fit on phones.
 */
import {
    Check,
    Ellipsis,
    Pencil,
    Plus,
    Repeat,
    RotateCcw,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import CategoryBadge from './CategoryBadge.vue';
import { formatDay, money } from './format';
import MonthSwitcher from './MonthSwitcher.vue';
import {
    contactLabel,
    contacts,
    bills,
    byCategory,
    categoryName,
    daysBetween,
    confirmingDelete,
    openEdit,
    restoreExpense,
    undoPayment,
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
const showDeleted = ref(false);

const contactName = (id?: string | null): string | undefined =>
    contacts.find((contact) => contact.id === id)?.name;
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
            <div class="flex justify-end">
                <MonthSwitcher class="-mr-1.5" />
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
                        <CategoryBadge
                            :category-id="occurrence.bill.categoryId"
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
                        <Button
                            v-else-if="occurrence.status !== 'skipped'"
                            size="sm"
                            variant="outline"
                            @click="paying = occurrence"
                        >
                            Paid
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    class="-mr-1 rounded-full p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                                    :aria-label="`More for ${occurrence.bill.name}`"
                                >
                                    <Ellipsis class="size-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                    v-if="occurrence.status === 'paid'"
                                    @select="undoPayment(occurrence)"
                                >
                                    <RotateCcw /> Mark unpaid
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-else-if="occurrence.status === 'skipped'"
                                    @select="toggleSkip(occurrence)"
                                >
                                    <RotateCcw /> Un-skip
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-else
                                    @select="toggleSkip(occurrence)"
                                >
                                    Skip this month
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
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
                        <CategoryBadge :category-id="expense.categoryId" />
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-sm">
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
                                <template v-if="contactName(expense.paidToId)">
                                    · to {{ contactName(expense.paidToId) }}
                                </template>
                            </p>
                        </div>
                        <span class="text-sm tabular-nums">
                            {{ money(expense.amountCents) }}
                        </span>
                        <div
                            class="flex gap-1 md:opacity-0 md:group-focus-within:opacity-100 md:group-hover:opacity-100"
                        >
                            <button
                                type="button"
                                class="rounded-full p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                                aria-label="Edit expense"
                                @click="openEdit(expense)"
                            >
                                <Pencil class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="rounded-full p-1 text-muted-foreground hover:bg-accent hover:text-destructive"
                                aria-label="Delete expense"
                                @click="confirmingDelete = expense"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                    </li>
                </ul>
            </section>
        </div>

        <section
            class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-2xl border bg-card px-4 py-3"
        >
            <CategoryBadge category-id="home" />
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
                        <td class="px-4 py-2">
                            <span class="flex items-center gap-2">
                                <CategoryBadge
                                    :category-id="row.id"
                                    size="sm"
                                />
                                {{ row.name }}
                            </span>
                        </td>
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
                    <CategoryBadge :category-id="bill.categoryId" />
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

        <section class="rounded-2xl border bg-card">
            <button
                type="button"
                class="flex w-full items-center justify-between px-4 py-3 text-sm font-semibold"
                :aria-expanded="showDeleted"
                @click="showDeleted = !showDeleted"
            >
                Recently deleted · {{ state.deleted.length }}
                <span class="text-xs font-normal text-muted-foreground">
                    {{ showDeleted ? 'Hide' : 'Show' }}
                </span>
            </button>
            <div v-if="showDeleted" class="border-t">
                <p
                    v-if="state.deleted.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    Deleted expenses and undone payments show up here for 30
                    days.
                </p>
                <ul class="divide-y">
                    <li
                        v-for="entry in state.deleted"
                        :key="entry.expense.id"
                        class="flex items-center gap-3 px-4 py-2.5"
                    >
                        <CategoryBadge
                            :category-id="entry.expense.categoryId"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm">
                                {{
                                    entry.expense.note ||
                                    categoryName(entry.expense.categoryId)
                                }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ money(entry.expense.amountCents) }} · paid
                                {{ formatDay(entry.expense.paidOn) }} ·
                                {{
                                    entry.reason === 'unpaid'
                                        ? 'marked unpaid'
                                        : 'deleted'
                                }}
                                {{ entry.at.toLocaleTimeString() }}
                            </p>
                        </div>
                        <Button
                            size="sm"
                            variant="outline"
                            @click="restoreExpense(entry.expense)"
                        >
                            Restore
                        </Button>
                    </li>
                </ul>
            </div>
        </section>
    </div>
</template>
