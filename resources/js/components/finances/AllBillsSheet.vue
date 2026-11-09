<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Archive, Ellipsis, Pencil } from '@lucide/vue';
import { computed } from 'vue';
import CommitmentController from '@/actions/App/Http/Controllers/Finances/CommitmentController';
import CategoryBadge from '@/components/finances/CategoryBadge.vue';
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
import { formatCents, formatDay } from '@/lib/money';
import { describeSchedule } from '@/lib/schedule';
import type { Category, Commitment } from '@/types';

/** Every bill: upcoming ones by next due date, then finished, then archived. */
const props = defineProps<{
    bills: Commitment[];
    categoryFor: (categoryId: number) => Category;
    paymentMethodName: (paymentMethodId: number | null) => string | undefined;
}>();

const open = defineModel<boolean>('open', { required: true });
const emit = defineEmits<{ edit: [bill: Commitment] }>();

const upcoming = computed(() =>
    props.bills
        .filter((bill) => !bill.archived && bill.next_due_on !== null)
        .sort((a, b) => a.next_due_on!.localeCompare(b.next_due_on!)),
);
const finished = computed(() =>
    props.bills.filter((bill) => !bill.archived && bill.next_due_on === null),
);
const archived = computed(() => props.bills.filter((bill) => bill.archived));

const sections = computed(() =>
    [
        { title: 'Upcoming', bills: upcoming.value },
        { title: 'Finished', bills: finished.value },
        { title: 'Archived', bills: archived.value },
    ].filter((section) => section.bills.length > 0),
);

function status(bill: Commitment): string {
    const method = props.paymentMethodName(bill.payment_method_id);
    const when = bill.next_due_on
        ? `Next ${formatDay(bill.next_due_on)}`
        : bill.ends_on
          ? `Final payment ${formatDay(bill.ends_on)}`
          : 'No more payments';

    return method ? `${when} · ${method}` : when;
}

function edit(bill: Commitment): void {
    open.value = false;
    emit('edit', bill);
}

function archive(bill: Commitment): void {
    router.visit(CommitmentController.archive(bill.id), {
        preserveScroll: true,
        preserveState: true,
    });
}

function unarchive(bill: Commitment): void {
    router.visit(CommitmentController.unarchive(bill.id), {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent class="w-full overflow-y-auto sm:max-w-md">
            <SheetHeader>
                <SheetTitle>All bills</SheetTitle>
                <SheetDescription>
                    Every bill and when it's next due. Archived bills stay in
                    the months before they were archived.
                </SheetDescription>
            </SheetHeader>
            <p
                v-if="bills.length === 0"
                class="px-4 py-6 text-center text-sm text-muted-foreground"
            >
                No bills yet.
            </p>
            <section
                v-for="section in sections"
                :key="section.title"
                class="px-4 pb-4"
            >
                <h3 class="py-1 text-xs font-medium text-muted-foreground">
                    {{ section.title }} · {{ section.bills.length }}
                </h3>
                <ul class="divide-y">
                    <li
                        v-for="bill in section.bills"
                        :key="bill.id"
                        class="flex items-center gap-3 py-2"
                        :class="bill.archived && 'text-muted-foreground'"
                    >
                        <CategoryBadge
                            :category="categoryFor(bill.category_id)"
                            :class="bill.archived && 'opacity-60'"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm leading-5">
                                {{ bill.name }}
                            </p>
                            <p
                                class="truncate text-[11px] leading-4 text-muted-foreground"
                            >
                                {{ describeSchedule(bill) }}
                            </p>
                            <p
                                class="truncate text-[11px] leading-4 text-muted-foreground"
                            >
                                {{ status(bill) }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 text-sm tabular-nums"
                            :class="
                                bill.next_amount_cents === null &&
                                'text-xs text-muted-foreground'
                            "
                        >
                            {{
                                bill.next_amount_cents !== null
                                    ? formatCents(bill.next_amount_cents)
                                    : bill.amount_varies
                                      ? 'Varies'
                                      : ''
                            }}
                        </span>
                        <Button
                            v-if="bill.archived"
                            size="sm"
                            variant="outline"
                            :data-test="`unarchive-bill-${bill.id}`"
                            @click="unarchive(bill)"
                        >
                            Unarchive
                        </Button>
                        <DropdownMenu v-else>
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    class="-mr-1 shrink-0 rounded-full p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                                    :aria-label="`More for ${bill.name}`"
                                    :data-test="`all-bills-actions-${bill.id}`"
                                >
                                    <Ellipsis class="size-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem @select="edit(bill)">
                                    <Pencil /> Edit bill
                                </DropdownMenuItem>
                                <DropdownMenuItem @select="archive(bill)">
                                    <Archive /> Archive bill
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </li>
                </ul>
            </section>
        </SheetContent>
    </Sheet>
</template>
