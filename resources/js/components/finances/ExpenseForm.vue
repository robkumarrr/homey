<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import ExpenseController from '@/actions/App/Http/Controllers/Finances/ExpenseController';
import CategorySelect from '@/components/finances/CategorySelect.vue';
import PaymentMethodSelect from '@/components/finances/PaymentMethodSelect.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { centsToInput, todayIso } from '@/lib/money';
import type { Category, Expense, PaymentMethod } from '@/types';

/** Record a new expense, or edit `expense` when one is given. */
defineProps<{
    expense: Expense | null;
    categories: Category[];
    paymentMethods: PaymentMethod[];
}>();

const emit = defineEmits<{ saved: [] }>();
</script>

<template>
    <Form
        v-bind="
            expense
                ? ExpenseController.update.form(expense.id)
                : ExpenseController.store.form()
        "
        :options="{ preserveScroll: true }"
        class="grid content-start gap-4"
        v-slot="{ errors, processing }"
        @success="emit('saved')"
    >
        <div class="grid gap-2">
            <Label for="expense-name">Name</Label>
            <Input
                id="expense-name"
                name="name"
                placeholder="Loblaws"
                autocomplete="off"
                required
                :default-value="expense?.name ?? ''"
                :aria-invalid="!!errors.name"
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="expense-amount">Amount</Label>
            <Input
                id="expense-amount"
                name="amount"
                inputmode="decimal"
                placeholder="0.00"
                autocomplete="off"
                :default-value="
                    expense ? centsToInput(expense.amount_cents) : ''
                "
                :aria-invalid="!!errors.amount"
            />
            <InputError :message="errors.amount" />
        </div>

        <div class="grid grid-cols-2 gap-3 [&>*]:min-w-0">
            <div class="grid gap-2">
                <Label for="expense-category">Category</Label>
                <CategorySelect
                    id="expense-category"
                    :categories="categories"
                    :keep-id="expense?.category_id"
                    :invalid="!!errors.category_id"
                />
                <InputError :message="errors.category_id" />
            </div>
            <div class="grid gap-2">
                <Label for="expense-date">Date</Label>
                <Input
                    id="expense-date"
                    name="paid_on"
                    type="date"
                    :default-value="expense?.paid_on ?? todayIso()"
                    :aria-invalid="!!errors.paid_on"
                />
                <InputError :message="errors.paid_on" />
            </div>
        </div>

        <PaymentMethodSelect
            id="expense-payment-method"
            :payment-methods="paymentMethods"
            :keep-id="expense?.payment_method_id"
            :error="errors.payment_method_id"
        />

        <DialogFooter>
            <Button
                type="submit"
                :disabled="processing"
                data-test="save-expense"
            >
                {{ expense ? 'Save changes' : 'Add expense' }}
            </Button>
        </DialogFooter>
    </Form>
</template>
