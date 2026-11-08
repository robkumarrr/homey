<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import ExpenseController from '@/actions/App/Http/Controllers/Finances/ExpenseController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatCents, formatDay } from '@/lib/money';
import type { Expense } from '@/types';

/** "Are you sure?" before an expense goes to Recently deleted. */
const expense = defineModel<Expense | null>('expense', { required: true });

function confirm(): void {
    router.visit(ExpenseController.destroy(expense.value!.id), {
        preserveScroll: true,
        onSuccess: () => (expense.value = null),
    });
}
</script>

<template>
    <Dialog
        :open="expense !== null"
        @update:open="(open) => !open && (expense = null)"
    >
        <DialogContent
            v-if="expense"
            class="max-h-[calc(100dvh-2rem)] overflow-y-auto p-5 sm:max-w-sm sm:p-6"
        >
            <DialogHeader>
                <DialogTitle>Delete this expense?</DialogTitle>
                <DialogDescription>
                    {{ expense.name }} ·
                    {{ formatCents(expense.amount_cents) }} ·
                    {{ formatDay(expense.paid_on) }}. You can undo right after,
                    or restore it from Recently deleted for 30 days.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="expense = null"
                    >Cancel</Button
                >
                <Button
                    variant="destructive"
                    data-test="confirm-delete"
                    @click="confirm"
                >
                    Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
