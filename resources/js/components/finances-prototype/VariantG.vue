<script setup lang="ts">
/**
 * PROTOTYPE variant G — "Ledger v5": F plus feedback. "Due this month" is now
 * "Recurring"; All bills and Recently deleted are quiet buttons beside a
 * smaller + Add and open in a side sheet; the totals stack on phones; the
 * untitled project strip and the By category table are gone, replaced by a
 * sort control on each list (date by default).
 */
import {
    Check,
    Ellipsis,
    History,
    ListChecks,
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
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import CategoryBadge from './CategoryBadge.vue';
import { formatDay, money } from './format';
import MonthSwitcher from './MonthSwitcher.vue';
import SortMenu from './SortMenu.vue';
import {
    bills,
    categories,
    categoryName,
    confirmingDelete,
    contactLabel,
    contacts,
    daysBetween,
    lastPaidCents,
    monthExpenses,
    monthOccurrences,
    openAdd,
    openEdit,
    paying,
    paymentMethodName,
    repeatLabels,
    restoreExpense,
    state,
    today,
    toggleSkip,
    totals,
    undoPayment,
} from './store';
import type { Direction, Occurrence, SortKey } from './store';

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

const contactName = (id?: string | null): string | undefined =>
    contacts.find((contact) => contact.id === id)?.name;

const categoryOrder = (id: string): number =>
    categories.findIndex((category) => category.id === id);

type Sortable = {
    date: string;
    amount: number;
    category: string;
    name: string;
};

function compare(
    a: Sortable,
    b: Sortable,
    key: SortKey,
    direction: Direction,
): number {
    const sign = direction === 'asc' ? 1 : -1;
    const primary =
        key === 'amount'
            ? a.amount - b.amount
            : key === 'category'
              ? categoryOrder(a.category) - categoryOrder(b.category)
              : key === 'name'
                ? a.name.localeCompare(b.name)
                : a.date.localeCompare(b.date);

    return sign * primary || a.date.localeCompare(b.date);
}

const recurringSort = ref<SortKey>('date');
const recurringDirection = ref<Direction>('asc');
const expenseSort = ref<SortKey>('date');
const expenseDirection = ref<Direction>('desc');

const recurring = computed(() =>
    [...monthOccurrences.value].sort((a, b) =>
        compare(
            {
                date: a.dueOn,
                amount: a.amountCents,
                category: a.bill.categoryId,
                name: a.bill.name,
            },
            {
                date: b.dueOn,
                amount: b.amountCents,
                category: b.bill.categoryId,
                name: b.bill.name,
            },
            recurringSort.value,
            recurringDirection.value,
        ),
    ),
);

const expenses = computed(() =>
    [...monthExpenses.value].sort((a, b) =>
        compare(
            {
                date: a.paidOn,
                amount: a.amountCents,
                category: a.categoryId,
                name: a.note,
            },
            {
                date: b.paidOn,
                amount: b.amountCents,
                category: b.categoryId,
                name: b.note,
            },
            expenseSort.value,
            expenseDirection.value,
        ),
    ),
);

const recurringBills = computed(() =>
    bills
        .filter((bill) => !bill.project)
        .sort((a, b) => a.name.localeCompare(b.name)),
);

const panel = ref<'bills' | 'deleted' | null>(null);
</script>

<template>
    <div class="space-y-6">
        <div class="space-y-2">
            <div class="flex items-center justify-between gap-2">
                <h1 class="text-2xl font-semibold tracking-tight md:text-3xl">
                    Finances
                </h1>
                <div class="flex items-center gap-1.5">
                    <Button
                        size="sm"
                        variant="ghost"
                        class="text-muted-foreground"
                        aria-label="All bills"
                        @click="panel = 'bills'"
                    >
                        <ListChecks />
                        <span class="hidden sm:inline">All bills</span>
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        class="text-muted-foreground"
                        aria-label="Recently deleted"
                        @click="panel = 'deleted'"
                    >
                        <History />
                        <span class="hidden sm:inline">Recently deleted</span>
                        <span
                            v-if="state.deleted.length"
                            class="rounded-full bg-muted px-1.5 text-[11px] tabular-nums"
                        >
                            {{ state.deleted.length }}
                        </span>
                    </Button>
                    <Button
                        size="sm"
                        class="px-2.5"
                        @click="openAdd('expense')"
                    >
                        <Plus /> Add
                    </Button>
                </div>
            </div>
            <div class="flex justify-end">
                <MonthSwitcher class="-mr-1.5" />
            </div>
        </div>

        <!-- Phones: one card with three rows. Wider: three tiles. -->
        <section
            class="grid divide-y rounded-2xl border bg-card sm:grid-cols-3 sm:divide-x sm:divide-y-0"
        >
            <div
                v-for="tile in [
                    { label: 'Spent so far', cents: totals.spent },
                    { label: 'Still due', cents: totals.stillDue },
                    { label: 'Projected', cents: totals.projected },
                ]"
                :key="tile.label"
                class="flex items-baseline justify-between gap-3 px-4 py-2.5 sm:block sm:p-4"
            >
                <p class="text-xs text-muted-foreground">{{ tile.label }}</p>
                <p class="font-semibold tabular-nums sm:mt-1 sm:text-2xl">
                    {{ money(tile.cents) }}
                </p>
            </div>
        </section>

        <div class="grid min-w-0 gap-6 md:grid-cols-2">
            <section class="min-w-0 rounded-2xl border bg-card">
                <div
                    class="flex items-center justify-between gap-2 border-b px-4 py-2.5"
                >
                    <h2 class="text-sm font-semibold">Recurring</h2>
                    <div class="flex items-center gap-1">
                        <SortMenu
                            v-model:sort-key="recurringSort"
                            v-model:direction="recurringDirection"
                            date-direction="asc"
                        />
                        <button
                            type="button"
                            class="flex items-center gap-1 rounded-full px-2 py-1 text-xs text-muted-foreground hover:bg-accent hover:text-foreground"
                            @click="openAdd('bill')"
                        >
                            <Plus class="size-3.5" /> Bill
                        </button>
                    </div>
                </div>
                <p
                    v-if="recurring.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    No bills due this month.
                </p>
                <ul class="divide-y">
                    <li
                        v-for="occurrence in recurring"
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
                                class="truncate text-xs"
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
                        <span class="shrink-0 text-sm tabular-nums">
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
                            class="size-4 shrink-0 text-(--meter-ok)"
                            aria-label="Paid"
                        />
                        <Button
                            v-else-if="occurrence.status !== 'skipped'"
                            size="sm"
                            variant="outline"
                            class="h-7 px-2"
                            @click="paying = occurrence"
                        >
                            Paid
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    class="-mr-1 shrink-0 rounded-full p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
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

            <section class="min-w-0 rounded-2xl border bg-card">
                <div
                    class="flex items-center justify-between gap-2 border-b px-4 py-2.5"
                >
                    <h2 class="text-sm font-semibold">
                        Expenses
                        <span class="font-normal text-muted-foreground">
                            · {{ expenses.length }}
                        </span>
                    </h2>
                    <div class="flex items-center gap-1">
                        <SortMenu
                            v-model:sort-key="expenseSort"
                            v-model:direction="expenseDirection"
                            date-direction="desc"
                        />
                        <button
                            type="button"
                            class="flex items-center gap-1 rounded-full px-2 py-1 text-xs text-muted-foreground hover:bg-accent hover:text-foreground"
                            @click="openAdd('expense')"
                        >
                            <Plus class="size-3.5" /> Expense
                        </button>
                    </div>
                </div>
                <p
                    v-if="expenses.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    Nothing spent this month.
                </p>
                <ul class="max-h-[32rem] divide-y overflow-y-auto">
                    <li
                        v-for="expense in expenses"
                        :key="expense.id"
                        class="group flex items-center gap-3 px-4 py-2.5"
                    >
                        <CategoryBadge :category-id="expense.categoryId" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm">
                                {{
                                    expense.note ||
                                    categoryName(expense.categoryId)
                                }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ formatDay(expense.paidOn) }} ·
                                {{ paymentMethodName(expense.paymentMethodId) }}
                                <template v-if="contactName(expense.paidToId)">
                                    · to {{ contactName(expense.paidToId) }}
                                </template>
                            </p>
                        </div>
                        <span class="shrink-0 text-sm tabular-nums">
                            {{ money(expense.amountCents) }}
                        </span>
                        <div
                            class="flex shrink-0 md:opacity-0 md:group-focus-within:opacity-100 md:group-hover:opacity-100"
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
                                class="-mr-1 rounded-full p-1 text-muted-foreground hover:bg-accent hover:text-destructive"
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

        <Sheet
            :open="panel !== null"
            @update:open="(open) => !open && (panel = null)"
        >
            <SheetContent class="w-full overflow-y-auto sm:max-w-md">
                <template v-if="panel === 'bills'">
                    <SheetHeader>
                        <SheetTitle
                            >All bills · {{ recurringBills.length }}</SheetTitle
                        >
                        <SheetDescription>
                            Every recurring bill, whether or not it's due this
                            month.
                        </SheetDescription>
                    </SheetHeader>
                    <ul class="divide-y px-4">
                        <li
                            v-for="bill in recurringBills"
                            :key="bill.id"
                            class="flex items-center gap-3 py-2.5"
                        >
                            <CategoryBadge :category-id="bill.categoryId" />
                            <div class="min-w-0 flex-1">
                                <p class="flex items-center gap-1.5 text-sm">
                                    <span class="truncate">{{
                                        bill.name
                                    }}</span>
                                    <Repeat
                                        v-if="bill.autopay"
                                        class="size-3.5 shrink-0 text-muted-foreground"
                                        aria-label="Autopay"
                                    />
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ repeatLabels[bill.everyMonths] }} ·
                                    {{
                                        paymentMethodName(bill.paymentMethodId)
                                    }}
                                    <template
                                        v-if="contactLabel(bill.contactId)"
                                    >
                                        · {{ contactLabel(bill.contactId) }}
                                    </template>
                                </p>
                            </div>
                            <span class="shrink-0 text-sm tabular-nums">
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
                </template>

                <template v-else-if="panel === 'deleted'">
                    <SheetHeader>
                        <SheetTitle>Recently deleted</SheetTitle>
                        <SheetDescription>
                            Deleted expenses and undone payments stay here for
                            30 days.
                        </SheetDescription>
                    </SheetHeader>
                    <p
                        v-if="state.deleted.length === 0"
                        class="px-4 py-6 text-center text-sm text-muted-foreground"
                    >
                        Nothing deleted.
                    </p>
                    <ul class="divide-y px-4">
                        <li
                            v-for="entry in state.deleted"
                            :key="entry.expense.id"
                            class="flex items-center gap-3 py-2.5"
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
                                    {{ money(entry.expense.amountCents) }} ·
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
                </template>
            </SheetContent>
        </Sheet>
    </div>
</template>
