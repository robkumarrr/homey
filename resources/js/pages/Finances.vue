<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Ellipsis, Pencil, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import CategoryBadge from '@/components/finances/CategoryBadge.vue';
import ExpenseDialog from '@/components/finances/ExpenseDialog.vue';
import MonthSwitcher from '@/components/finances/MonthSwitcher.vue';
import SortMenu from '@/components/finances/SortMenu.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatCents, formatDay } from '@/lib/money';
import type { Category, Expense, SortDirection, SortKey } from '@/types';

const props = defineProps<{
    month: string;
    categories: Category[];
    expenses: Expense[];
    spentCents: number;
}>();

const categoriesById = computed(
    () => new Map(props.categories.map((category) => [category.id, category])),
);
const categoryFor = (expense: Expense): Category =>
    categoriesById.value.get(expense.category_id)!;
const categoryPosition = (expense: Expense): number =>
    props.categories.indexOf(categoryFor(expense));

const sortKey = ref<SortKey>('date');
const sortDirection = ref<SortDirection>('desc');

const sortedExpenses = computed(() => {
    const sign = sortDirection.value === 'asc' ? 1 : -1;

    return [...props.expenses].sort((a, b) => {
        const primary =
            sortKey.value === 'amount'
                ? a.amount_cents - b.amount_cents
                : sortKey.value === 'category'
                  ? categoryPosition(a) - categoryPosition(b)
                  : sortKey.value === 'name'
                    ? a.name.localeCompare(b.name)
                    : a.paid_on.localeCompare(b.paid_on);

        return (
            sign * primary || b.paid_on.localeCompare(a.paid_on) || b.id - a.id
        );
    });
});

const dialogOpen = ref(false);
const editing = ref<Expense | null>(null);

function openAdd(): void {
    editing.value = null;
    dialogOpen.value = true;
}

function openEdit(expense: Expense): void {
    editing.value = expense;
    dialogOpen.value = true;
}
</script>

<template>
    <div class="space-y-6">
        <Head title="Finances" />

        <div class="space-y-2">
            <div class="flex items-center justify-between gap-2">
                <h1 class="text-2xl font-semibold tracking-tight md:text-3xl">
                    Finances
                </h1>
                <Button
                    size="sm"
                    class="px-2.5"
                    data-test="add-button"
                    @click="openAdd"
                >
                    <Plus /> Add
                </Button>
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
                        @click="openAdd"
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
                    <CategoryBadge :category="categoryFor(expense)" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm leading-5">
                            {{ expense.name }}
                        </p>
                        <p
                            class="truncate text-[11px] leading-4 text-muted-foreground"
                        >
                            {{ formatDay(expense.paid_on) }}
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
                            <DropdownMenuItem @select="openEdit(expense)">
                                <Pencil /> Edit
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </li>
            </ul>
        </section>

        <ExpenseDialog
            v-model:open="dialogOpen"
            :expense="editing"
            :categories="categories"
        />
    </div>
</template>
