<script setup lang="ts">
/**
 * PROTOTYPE variant H — "Ledger v6": G with a consistent bill status slot
 * (Pay button, Paid and Skipped pills, all the same size), the three total
 * tiles back as separate cards, rows split onto three compact lines (name;
 * status or date with the contractor; payment method), list header controls
 * styled as buttons, a count on Recurring, and the same always-visible ⋯
 * menu on expenses (Edit, Delete…) as on bills.
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
    SkipForward,
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

function statusText(occurrence: Occurrence): string {
    if (occurrence.status === 'paid') {
        return `Paid ${formatDay(occurrence.expense!.paidOn)}`;
    }

    if (occurrence.status === 'skipped') {
        return `Skipped · was due ${formatDay(occurrence.dueOn)}`;
    }

    const days = daysBetween(today, occurrence.dueOn);

    if (days < 0) {
        return `${-days} day${days === -1 ? '' : 's'} overdue`;
    }

    return days === 0 ? 'Due today' : `Due ${formatDay(occurrence.dueOn)}`;
}

/** Third line: where the money came from, or will come from. */
const methodText = (occurrence: Occurrence): string =>
    paymentMethodName(
        occurrence.expense?.paymentMethodId ?? occurrence.bill.paymentMethodId,
    );

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

const paidCount = computed(
    () => recurring.value.filter((o) => o.status === 'paid').length,
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

const headerButton =
    'flex h-7 items-center gap-1 rounded-md border bg-background px-2 text-xs text-muted-foreground shadow-xs hover:bg-accent hover:text-foreground dark:border-input dark:bg-input/30';
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

        <section class="grid grid-cols-3 gap-2 sm:gap-3">
            <div
                v-for="tile in [
                    { label: 'Spent so far', cents: totals.spent },
                    { label: 'Still due', cents: totals.stillDue },
                    { label: 'Projected', cents: totals.projected },
                ]"
                :key="tile.label"
                class="min-w-0 rounded-2xl border bg-card p-3 sm:p-4"
            >
                <p
                    class="truncate text-[11px] text-muted-foreground sm:text-xs"
                >
                    {{ tile.label }}
                </p>
                <p
                    class="mt-1 text-[15px] font-semibold tabular-nums sm:text-2xl"
                >
                    {{ money(tile.cents) }}
                </p>
            </div>
        </section>

        <div class="grid min-w-0 gap-6 md:grid-cols-2">
            <section class="min-w-0 rounded-2xl border bg-card">
                <div
                    class="flex items-center justify-between gap-2 border-b px-4 py-2.5"
                >
                    <h2 class="text-sm font-semibold">
                        Recurring
                        <span class="font-normal text-muted-foreground">
                            · {{ paidCount }}/{{ recurring.length }} paid
                        </span>
                    </h2>
                    <div class="flex items-center gap-1.5">
                        <SortMenu
                            v-model:sort-key="recurringSort"
                            v-model:direction="recurringDirection"
                            date-direction="asc"
                            :trigger-class="headerButton"
                        />
                        <button
                            type="button"
                            :class="headerButton"
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
                        class="flex items-center gap-3 px-4 py-2"
                    >
                        <CategoryBadge
                            :category-id="occurrence.bill.categoryId"
                            :class="
                                occurrence.status === 'skipped' && 'opacity-50'
                            "
                        />
                        <div class="min-w-0 flex-1">
                            <p
                                class="flex items-center gap-1.5 text-sm leading-5 font-medium"
                            >
                                <span
                                    class="truncate"
                                    :class="
                                        occurrence.status === 'skipped' &&
                                        'text-muted-foreground line-through'
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
                                class="truncate text-[11px] leading-4"
                                :class="
                                    occurrence.status === 'overdue'
                                        ? 'font-medium text-destructive'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ statusText(occurrence) }}
                                <template v-if="occurrence.bill.project">
                                    · {{ occurrence.bill.project.contractor }}
                                </template>
                            </p>
                            <p
                                class="truncate text-[11px] leading-4 text-muted-foreground"
                            >
                                {{ methodText(occurrence) }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 text-sm tabular-nums"
                            :class="
                                occurrence.status === 'skipped' &&
                                'text-muted-foreground line-through'
                            "
                        >
                            <span
                                v-if="
                                    occurrence.bill.variable &&
                                    occurrence.status !== 'paid'
                                "
                                class="text-muted-foreground"
                                >~</span
                            >{{ money(occurrence.amountCents) }}
                        </span>

                        <!-- One fixed-size status slot: Pay button, or a Paid / Skipped pill. -->
                        <span
                            v-if="occurrence.status === 'paid'"
                            class="flex h-7 w-17 shrink-0 items-center justify-center gap-1 rounded-md bg-green-100 text-xs font-semibold text-green-600 dark:bg-green-950 dark:text-green-400"
                        >
                            <Check class="size-3.5" /> Paid
                        </span>
                        <span
                            v-else-if="occurrence.status === 'skipped'"
                            class="flex h-7 w-17 shrink-0 items-center justify-center gap-1 rounded-md bg-muted text-xs text-muted-foreground"
                        >
                            <SkipForward class="size-3.5" /> Skipped
                        </span>
                        <Button
                            v-else
                            size="sm"
                            :variant="
                                occurrence.status === 'overdue'
                                    ? 'default'
                                    : 'outline'
                            "
                            class="h-7 w-17 shrink-0 px-0 text-xs"
                            @click="paying = occurrence"
                        >
                            Pay
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
                                    <SkipForward /> Skip this month
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
                    <div class="flex items-center gap-1.5">
                        <SortMenu
                            v-model:sort-key="expenseSort"
                            v-model:direction="expenseDirection"
                            date-direction="desc"
                            :trigger-class="headerButton"
                        />
                        <button
                            type="button"
                            :class="headerButton"
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
                <ul class="max-h-[36rem] divide-y overflow-y-auto">
                    <li
                        v-for="expense in expenses"
                        :key="expense.id"
                        class="flex items-center gap-3 px-4 py-2"
                    >
                        <CategoryBadge :category-id="expense.categoryId" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm leading-5">
                                {{
                                    expense.note ||
                                    categoryName(expense.categoryId)
                                }}
                            </p>
                            <p
                                class="truncate text-[11px] leading-4 text-muted-foreground"
                            >
                                {{ formatDay(expense.paidOn) }}
                                <template v-if="contactName(expense.paidToId)">
                                    · {{ contactName(expense.paidToId) }}
                                </template>
                            </p>
                            <p
                                class="truncate text-[11px] leading-4 text-muted-foreground"
                            >
                                {{ paymentMethodName(expense.paymentMethodId) }}
                            </p>
                        </div>
                        <span class="shrink-0 text-sm tabular-nums">
                            {{ money(expense.amountCents) }}
                        </span>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    class="-mr-1 shrink-0 rounded-full p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                                    :aria-label="`More for ${expense.note || categoryName(expense.categoryId)}`"
                                >
                                    <Ellipsis class="size-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem @select="openEdit(expense)">
                                    <Pencil /> Edit
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    variant="destructive"
                                    @select="confirmingDelete = expense"
                                >
                                    <Trash2 /> Delete…
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
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
                            class="flex items-center gap-3 py-2"
                        >
                            <CategoryBadge :category-id="bill.categoryId" />
                            <div class="min-w-0 flex-1">
                                <p
                                    class="flex items-center gap-1.5 text-sm leading-5"
                                >
                                    <span class="truncate">{{
                                        bill.name
                                    }}</span>
                                    <Repeat
                                        v-if="bill.autopay"
                                        class="size-3.5 shrink-0 text-muted-foreground"
                                        aria-label="Autopay"
                                    />
                                </p>
                                <p
                                    class="truncate text-[11px] leading-4 text-muted-foreground"
                                >
                                    {{ repeatLabels[bill.everyMonths] }}
                                    <template
                                        v-if="contactLabel(bill.contactId)"
                                    >
                                        · {{ contactLabel(bill.contactId) }}
                                    </template>
                                </p>
                                <p
                                    class="truncate text-[11px] leading-4 text-muted-foreground"
                                >
                                    {{
                                        paymentMethodName(bill.paymentMethodId)
                                    }}
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
                            class="flex items-center gap-3 py-2"
                        >
                            <CategoryBadge
                                :category-id="entry.expense.categoryId"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm leading-5">
                                    {{
                                        entry.expense.note ||
                                        categoryName(entry.expense.categoryId)
                                    }}
                                </p>
                                <p
                                    class="truncate text-[11px] leading-4 text-muted-foreground"
                                >
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
