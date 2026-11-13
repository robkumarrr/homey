<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import BillForm from '@/components/finances/BillForm.vue';
import ExpenseForm from '@/components/finances/ExpenseForm.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { defaultDateIn } from '@/lib/money';
import type { Category, Commitment, Expense, PaymentMethod } from '@/types';

/**
 * Adds an expense or a bill, with an Expense | Bill switch, or edits the
 * `expense` or `bill` given. Both forms share one grid cell, so the dialog
 * keeps the taller one's size when switching.
 */
const props = defineProps<{
    expense: Expense | null;
    bill: Commitment | null;
    categories: Category[];
    paymentMethods: PaymentMethod[];
    /** The month being viewed (YYYY-MM), which new entries default into. */
    month: string;
}>();

const open = defineModel<boolean>('open', { required: true });
const mode = defineModel<'expense' | 'bill'>('mode', { required: true });

const defaultDate = computed(() => defaultDateIn(props.month));

const editing = computed(() => props.expense !== null || props.bill !== null);

const title = computed(() =>
    mode.value === 'expense'
        ? props.expense
            ? 'Edit expense'
            : 'Add expense'
        : props.bill
          ? 'Edit bill'
          : 'Add bill',
);

/** Remount the forms on every open so their defaults are fresh. */
const formKey = ref(0);
watch(open, (isOpen) => isOpen && formKey.value++);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="max-h-[calc(100dvh-2rem)] overflow-y-auto p-5 sm:max-w-md sm:p-6"
        >
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{
                        mode === 'expense'
                            ? 'Money that left the household.'
                            : 'A payment due on a schedule or a set date.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="!editing"
                class="relative grid grid-cols-2 rounded-lg bg-muted p-1"
                role="tablist"
            >
                <span
                    class="absolute inset-y-1 left-1 w-[calc(50%-0.25rem)] rounded-md bg-card shadow-xs transition-transform duration-200 ease-out motion-reduce:transition-none"
                    :class="mode === 'bill' && 'translate-x-full'"
                    aria-hidden="true"
                />
                <button
                    v-for="option in ['expense', 'bill'] as const"
                    :key="option"
                    type="button"
                    role="tab"
                    :aria-selected="mode === option"
                    :data-test="`mode-${option}`"
                    class="relative rounded-md py-1.5 text-sm capitalize transition-colors"
                    :class="
                        mode === option
                            ? 'font-medium text-foreground'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="mode = option"
                >
                    {{ option }}
                </button>
            </div>

            <div class="grid [&>*]:[grid-area:1/1]">
                <ExpenseForm
                    v-if="!editing || expense"
                    :key="`expense-${formKey}`"
                    :expense="expense"
                    :categories="categories"
                    :payment-methods="paymentMethods"
                    :default-date="defaultDate"
                    class="transition-opacity duration-200"
                    :class="
                        mode !== 'expense' && 'pointer-events-none opacity-0'
                    "
                    :inert="mode !== 'expense'"
                    @saved="open = false"
                />
                <BillForm
                    v-if="!editing || bill"
                    :key="`bill-${formKey}`"
                    :bill="bill"
                    :categories="categories"
                    :payment-methods="paymentMethods"
                    :default-date="defaultDate"
                    class="transition-opacity duration-200"
                    :class="mode !== 'bill' && 'pointer-events-none opacity-0'"
                    :inert="mode !== 'bill'"
                    @saved="open = false"
                />
            </div>
        </DialogContent>
    </Dialog>
</template>
