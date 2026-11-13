<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Archive, CircleDollarSign, Ellipsis, Pencil, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import CommitmentController from '@/actions/App/Http/Controllers/Finances/CommitmentController';
import CategoryBadge from '@/components/finances/CategoryBadge.vue';
import SortMenu from '@/components/finances/SortMenu.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatCents, formatDay } from '@/lib/money';
import type {
    Category,
    Commitment,
    Occurrence,
    SortDirection,
    SortKey,
} from '@/types';

/** The month's bill due dates, soonest first by default. */
const props = defineProps<{
    occurrences: Occurrence[];
    billFor: (commitmentId: number) => Commitment;
    categoryFor: (categoryId: number) => Category;
    categoryPosition: (categoryId: number) => number;
    paymentMethodName: (paymentMethodId: number | null) => string | undefined;
}>();

const emit = defineEmits<{
    add: [];
    edit: [bill: Commitment];
    changeAmount: [bill: Commitment, occurrence: Occurrence];
}>();

const sortKey = ref<SortKey>('date');
const sortDirection = ref<SortDirection>('asc');

const rows = computed(() => {
    const sign = sortDirection.value === 'asc' ? 1 : -1;

    return props.occurrences
        .map((occurrence) => ({
            ...occurrence,
            bill: props.billFor(occurrence.commitment_id),
        }))
        .sort((a, b) => {
            const primary =
                sortKey.value === 'amount'
                    ? (a.amount_cents ?? -1) - (b.amount_cents ?? -1)
                    : sortKey.value === 'category'
                      ? props.categoryPosition(a.bill.category_id) -
                        props.categoryPosition(b.bill.category_id)
                      : sortKey.value === 'name'
                        ? a.bill.name.localeCompare(b.bill.name)
                        : a.due_on.localeCompare(b.due_on);

            return (
                sign * primary ||
                a.due_on.localeCompare(b.due_on) ||
                a.bill.name.localeCompare(b.bill.name)
            );
        });
});

function archive(bill: Commitment): void {
    router.visit(CommitmentController.archive(bill.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <section class="min-w-0 rounded-2xl border bg-card">
        <div
            class="flex items-center justify-between gap-2 border-b px-4 py-2.5"
        >
            <h2 class="text-sm font-semibold">
                Recurring
                <span class="font-normal text-muted-foreground">
                    · {{ occurrences.length }}
                </span>
            </h2>
            <div class="flex items-center gap-1.5">
                <SortMenu
                    v-model:sort-key="sortKey"
                    v-model:direction="sortDirection"
                    date-direction="asc"
                />
                <button
                    type="button"
                    class="flex h-7 items-center gap-1 rounded-md border bg-background px-2 text-xs text-muted-foreground shadow-xs hover:bg-accent hover:text-foreground dark:border-input dark:bg-input/30"
                    data-test="add-bill-button"
                    @click="emit('add')"
                >
                    <Plus class="size-3.5" /> Bill
                </button>
            </div>
        </div>

        <p
            v-if="occurrences.length === 0"
            class="px-4 py-10 text-center text-sm text-muted-foreground"
        >
            No bills due this month.
        </p>
        <ul class="divide-y">
            <li
                v-for="row in rows"
                :key="`${row.commitment_id}-${row.due_on}`"
                class="flex items-center gap-3 px-4 py-2"
            >
                <CategoryBadge :category="categoryFor(row.bill.category_id)" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm leading-5">
                        {{ row.bill.name }}
                    </p>
                    <p
                        class="truncate text-[11px] leading-4 text-muted-foreground"
                    >
                        Due {{ formatDay(row.due_on) }}
                    </p>
                    <p
                        v-if="paymentMethodName(row.bill.payment_method_id)"
                        class="truncate text-[11px] leading-4 text-muted-foreground"
                    >
                        {{ paymentMethodName(row.bill.payment_method_id) }}
                    </p>
                </div>
                <button
                    v-if="row.amount_cents === null"
                    type="button"
                    class="shrink-0 rounded-md border border-dashed px-2 py-0.5 text-xs text-muted-foreground hover:bg-accent hover:text-foreground"
                    data-test="enter-amount"
                    @click="emit('changeAmount', row.bill, row)"
                >
                    Enter amount
                </button>
                <span v-else class="shrink-0 text-sm tabular-nums">
                    {{ formatCents(row.amount_cents) }}
                </span>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="-mr-1 shrink-0 rounded-full p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                            :aria-label="`More for ${row.bill.name}`"
                            data-test="bill-actions"
                        >
                            <Ellipsis class="size-4" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem
                            @select="emit('changeAmount', row.bill, row)"
                        >
                            <CircleDollarSign />
                            {{
                                row.bill.amount_varies
                                    ? 'Enter amount…'
                                    : 'Change amount…'
                            }}
                        </DropdownMenuItem>
                        <DropdownMenuItem @select="emit('edit', row.bill)">
                            <Pencil /> Edit bill
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-if="!row.bill.archived"
                            @select="archive(row.bill)"
                        >
                            <Archive /> Archive bill
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </li>
        </ul>
    </section>
</template>
