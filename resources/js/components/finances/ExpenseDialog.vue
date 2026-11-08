<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ExpenseController from '@/actions/App/Http/Controllers/Finances/ExpenseController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { centsToInput, todayIso } from '@/lib/money';
import type { Category, Expense } from '@/types';

/** Add a new expense, or edit `expense` when one is given. */
const props = defineProps<{
    expense: Expense | null;
    categories: Category[];
}>();

const open = defineModel<boolean>('open', { required: true });

/** Remount the form on every open so its defaults are fresh. */
const formKey = ref(0);
watch(open, (isOpen) => isOpen && formKey.value++);

const selectableCategories = computed(() =>
    props.categories.filter(
        (category) =>
            !category.archived || category.id === props.expense?.category_id,
    ),
);

const defaultCategoryId = computed(
    () =>
        props.expense?.category_id ??
        selectableCategories.value.find((c) => c.name === 'Groceries')?.id ??
        selectableCategories.value[0]?.id,
);

const selectClass =
    'h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="max-h-[calc(100dvh-2rem)] overflow-y-auto p-5 sm:max-w-md sm:p-6"
        >
            <DialogHeader>
                <DialogTitle>
                    {{ expense ? 'Edit expense' : 'Add expense' }}
                </DialogTitle>
                <DialogDescription
                    >Money that left the household.</DialogDescription
                >
            </DialogHeader>

            <Form
                :key="formKey"
                v-bind="
                    expense
                        ? ExpenseController.update.form(expense.id)
                        : ExpenseController.store.form()
                "
                :options="{ preserveScroll: true }"
                class="grid gap-4"
                v-slot="{ errors, processing }"
                @success="open = false"
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
                        <select
                            id="expense-category"
                            name="category_id"
                            :class="selectClass"
                            :aria-invalid="!!errors.category_id"
                        >
                            <option
                                v-for="category in selectableCategories"
                                :key="category.id"
                                :value="category.id"
                                :selected="category.id === defaultCategoryId"
                            >
                                {{ category.name }}
                            </option>
                        </select>
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

                <DialogFooter>
                    <Button type="submit" :disabled="processing">
                        {{ expense ? 'Save changes' : 'Add expense' }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
