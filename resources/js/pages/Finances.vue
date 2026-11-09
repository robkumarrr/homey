<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Ellipsis,
    History,
    Pencil,
    Plus,
    Repeat,
    Tags,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import AllBillsSheet from '@/components/finances/AllBillsSheet.vue';
import AmountDialog from '@/components/finances/AmountDialog.vue';
import CategoryBadge from '@/components/finances/CategoryBadge.vue';
import DeleteExpenseDialog from '@/components/finances/DeleteExpenseDialog.vue';
import FinanceDialog from '@/components/finances/FinanceDialog.vue';
import MonthSwitcher from '@/components/finances/MonthSwitcher.vue';
import RecentlyDeletedSheet from '@/components/finances/RecentlyDeletedSheet.vue';
import RecurringList from '@/components/finances/RecurringList.vue';
import SortMenu from '@/components/finances/SortMenu.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatCents, formatDay } from '@/lib/money';
import { index as categoriesIndex } from '@/routes/categories';
import type {
    Category,
    Commitment,
    DeletedExpense,
    Expense,
    Occurrence,
    PaymentMethod,
    SortDirection,
    SortKey,
} from '@/types';

const props = defineProps<{
    month: string;
    categories: Category[];
    paymentMethods: PaymentMethod[];
    commitments: Commitment[];
    occurrences: Occurrence[];
    expenses: Expense[];
    spentCents: number;
    deletedExpenses: DeletedExpense[];
}>();

const categoriesById = computed(
    () => new Map(props.categories.map((category) => [category.id, category])),
);
const category = (categoryId: number): Category =>
    categoriesById.value.get(categoryId)!;
const categoryPosition = (categoryId: number): number =>
    props.categories.indexOf(category(categoryId));
const paymentMethodsById = computed(
    () => new Map(props.paymentMethods.map((method) => [method.id, method])),
);
const paymentMethodName = (
    paymentMethodId: number | null,
): string | undefined =>
    paymentMethodId === null
        ? undefined
        : paymentMethodsById.value.get(paymentMethodId)?.name;
const billsById = computed(
    () => new Map(props.commitments.map((bill) => [bill.id, bill])),
);
const bill = (commitmentId: number): Commitment =>
    billsById.value.get(commitmentId)!;

const sortKey = ref<SortKey>('date');
const sortDirection = ref<SortDirection>('desc');

const sortedExpenses = computed(() => {
    const sign = sortDirection.value === 'asc' ? 1 : -1;

    return [...props.expenses].sort((a, b) => {
        const primary =
            sortKey.value === 'amount'
                ? a.amount_cents - b.amount_cents
                : sortKey.value === 'category'
                  ? categoryPosition(a.category_id) -
                    categoryPosition(b.category_id)
                  : sortKey.value === 'name'
                    ? a.name.localeCompare(b.name)
                    : a.paid_on.localeCompare(b.paid_on);

        return (
            sign * primary || b.paid_on.localeCompare(a.paid_on) || b.id - a.id
        );
    });
});

const dialogOpen = ref(false);
const dialogMode = ref<'expense' | 'bill'>('expense');
const editingExpense = ref<Expense | null>(null);
const editingBill = ref<Commitment | null>(null);

function openAdd(mode: 'expense' | 'bill'): void {
    editingExpense.value = null;
    editingBill.value = null;
    dialogMode.value = mode;
    dialogOpen.value = true;
}

function openEditExpense(expense: Expense): void {
    editingExpense.value = expense;
    editingBill.value = null;
    dialogMode.value = 'expense';
    dialogOpen.value = true;
}

function openEditBill(commitment: Commitment): void {
    editingExpense.value = null;
    editingBill.value = commitment;
    dialogMode.value = 'bill';
    dialogOpen.value = true;
}

const showAllBills = ref(false);
const changingAmount = ref<{
    bill: Commitment;
    occurrence: Occurrence;
} | null>(null);

const deleting = ref<Expense | null>(null);
const showDeleted = ref(false);
</script>

<template>
    <div class="space-y-6">
        <Head title="Finances" />

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
                        as-child
                    >
                        <Link
                            :href="categoriesIndex()"
                            aria-label="Categories"
                            data-test="categories-link"
                        >
                            <Tags />
                            <span class="hidden sm:inline">Categories</span>
                        </Link>
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        class="text-muted-foreground"
                        aria-label="All bills"
                        data-test="all-bills"
                        @click="showAllBills = true"
                    >
                        <Repeat />
                        <span class="hidden sm:inline">All bills</span>
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        class="text-muted-foreground"
                        aria-label="Recently deleted"
                        data-test="recently-deleted"
                        @click="showDeleted = true"
                    >
                        <History />
                        <span class="hidden sm:inline">Recently deleted</span>
                        <span
                            v-if="deletedExpenses.length"
                            class="rounded-full bg-muted px-1.5 text-[11px] tabular-nums"
                        >
                            {{ deletedExpenses.length }}
                        </span>
                    </Button>
                    <Button
                        size="sm"
                        class="px-2.5"
                        data-test="add-button"
                        @click="openAdd('expense')"
                    >
                        <Plus /> Add
                    </Button>
                </div>
            </div>
            <div class="flex justify-end">
                <MonthSwitcher :month="month" class="-mr-1.5" />
            </div>
        </div>

        <section class="grid grid-cols-3 gap-2 sm:gap-3">
            <div class="min-w-0 rounded-2xl border bg-card p-3 sm:p-4">
                <p
                    class="truncate text-[11px] text-muted-foreground sm:text-xs"
                >
                    Spent so far
                </p>
                <p
                    class="mt-1 text-[15px] font-semibold tabular-nums sm:text-2xl"
                >
                    {{ formatCents(spentCents) }}
                </p>
            </div>
        </section>

        <div class="grid items-start gap-4 lg:grid-cols-2">
            <RecurringList
                :occurrences="occurrences"
                :bill-for="bill"
                :category-for="category"
                :category-position="categoryPosition"
                :payment-method-name="paymentMethodName"
                @add="openAdd('bill')"
                @edit="openEditBill"
                @change-amount="
                    (bill, occurrence) =>
                        (changingAmount = { bill, occurrence })
                "
            />

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
                            v-model:sort-key="sortKey"
                            v-model:direction="sortDirection"
                            date-direction="desc"
                        />
                        <button
                            type="button"
                            class="flex h-7 items-center gap-1 rounded-md border bg-background px-2 text-xs text-muted-foreground shadow-xs hover:bg-accent hover:text-foreground dark:border-input dark:bg-input/30"
                            @click="openAdd('expense')"
                        >
                            <Plus class="size-3.5" /> Expense
                        </button>
                    </div>
                </div>

                <p
                    v-if="expenses.length === 0"
                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                >
                    Nothing spent this month yet.
                </p>
                <ul class="divide-y">
                    <li
                        v-for="expense in sortedExpenses"
                        :key="expense.id"
                        class="flex items-center gap-3 px-4 py-2"
                    >
                        <CategoryBadge
                            :category="category(expense.category_id)"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm leading-5">
                                {{ expense.name }}
                            </p>
                            <p
                                class="truncate text-[11px] leading-4 text-muted-foreground"
                            >
                                {{ formatDay(expense.paid_on) }}
                            </p>
                            <p
                                v-if="
                                    paymentMethodName(expense.payment_method_id)
                                "
                                class="truncate text-[11px] leading-4 text-muted-foreground"
                            >
                                {{
                                    paymentMethodName(expense.payment_method_id)
                                }}
                            </p>
                        </div>
                        <span class="shrink-0 text-sm tabular-nums">
                            {{ formatCents(expense.amount_cents) }}
                        </span>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    class="-mr-1 shrink-0 rounded-full p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                                    :aria-label="`More for ${expense.name}`"
                                    data-test="expense-actions"
                                >
                                    <Ellipsis class="size-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                    @select="openEditExpense(expense)"
                                >
                                    <Pencil /> Edit
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    variant="destructive"
                                    @select="deleting = expense"
                                >
                                    <Trash2 /> Delete…
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </li>
                </ul>
            </section>
        </div>

        <DeleteExpenseDialog v-model:expense="deleting" />

        <RecentlyDeletedSheet
            v-model:open="showDeleted"
            :deleted-expenses="deletedExpenses"
            :category-for="category"
        />

        <AllBillsSheet
            v-model:open="showAllBills"
            :bills="commitments"
            :category-for="category"
            :payment-method-name="paymentMethodName"
            @edit="openEditBill"
        />

        <AmountDialog v-model:target="changingAmount" />

        <FinanceDialog
            v-model:open="dialogOpen"
            v-model:mode="dialogMode"
            :expense="editingExpense"
            :bill="editingBill"
            :categories="categories"
            :payment-methods="paymentMethods"
        />
    </div>
</template>
