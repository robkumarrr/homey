<script setup lang="ts">
/**
 * PROTOTYPE dialogs shared by every Finances variant: the Paid form for a bill
 * occurrence and the quick-add expense form. Both write to the in-memory store.
 */
import { ref, watch } from 'vue';
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
import { formatDay, parseCents } from './format';
import {
    addExpense,
    addingExpense,
    categories,
    lastPaidCents,
    payOccurrence,
    paying,
    paymentMethods,
    today,
} from './store';

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';

const paid = ref({ amount: '', paidOn: today, paymentMethodId: '' as string });
const paidError = ref('');

watch(paying, (occurrence) => {
    if (!occurrence) {
        return;
    }

    paid.value = {
        amount: (lastPaidCents(occurrence.bill) / 100).toFixed(2),
        paidOn: today,
        paymentMethodId: occurrence.bill.paymentMethodId,
    };
    paidError.value = '';
});

function submitPaid(): void {
    const cents = parseCents(paid.value.amount);

    if (cents === null || cents === 0) {
        paidError.value = 'Enter an amount like 19.99 or $1,200.';

        return;
    }

    payOccurrence(paying.value!, {
        amountCents: cents,
        paidOn: paid.value.paidOn,
        paymentMethodId: paid.value.paymentMethodId || null,
    });
    paying.value = null;
}

const blankExpense = () => ({
    amount: '',
    paidOn: today,
    categoryId: 'groceries',
    note: '',
    paymentMethodId: '',
});
const expense = ref(blankExpense());
const expenseError = ref('');

watch(addingExpense, (open) => {
    if (open) {
        expense.value = blankExpense();
        expenseError.value = '';
    }
});

function submitExpense(): void {
    const cents = parseCents(expense.value.amount);

    if (cents === null || cents === 0) {
        expenseError.value = 'Enter an amount like 19.99 or $1,200.';

        return;
    }

    addExpense({
        amountCents: cents,
        paidOn: expense.value.paidOn,
        categoryId: expense.value.categoryId,
        note: expense.value.note,
        paymentMethodId: expense.value.paymentMethodId || null,
    });
    addingExpense.value = false;
}
</script>

<template>
    <div>
        <Dialog
            :open="paying !== null"
            @update:open="(open) => !open && (paying = null)"
        >
            <DialogContent v-if="paying" class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Paid {{ paying.bill.name }}</DialogTitle>
                    <DialogDescription>
                        Due {{ formatDay(paying.dueOn) }}
                        <template v-if="paying.bill.variable">
                            · amount varies, pre-filled with last time's
                        </template>
                    </DialogDescription>
                </DialogHeader>
                <form class="grid gap-4" @submit.prevent="submitPaid">
                    <div class="grid gap-2">
                        <Label for="paid-amount">Amount</Label>
                        <Input
                            id="paid-amount"
                            v-model="paid.amount"
                            inputmode="decimal"
                            :aria-invalid="paidError !== ''"
                        />
                        <p v-if="paidError" class="text-sm text-destructive">
                            {{ paidError }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="paid-on">Paid on</Label>
                        <Input id="paid-on" v-model="paid.paidOn" type="date" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="paid-method">Paid from</Label>
                        <select
                            id="paid-method"
                            v-model="paid.paymentMethodId"
                            :class="selectClass"
                        >
                            <option value="">Not recorded</option>
                            <option
                                v-for="method in paymentMethods"
                                :key="method.id"
                                :value="method.id"
                            >
                                {{ method.name }}
                            </option>
                        </select>
                    </div>
                    <DialogFooter>
                        <Button type="submit">Record payment</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="addingExpense"
            @update:open="(open) => (addingExpense = open)"
        >
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Add expense</DialogTitle>
                    <DialogDescription>
                        Money that left the household.
                    </DialogDescription>
                </DialogHeader>
                <form class="grid gap-4" @submit.prevent="submitExpense">
                    <div class="grid gap-2">
                        <Label for="expense-amount">Amount</Label>
                        <Input
                            id="expense-amount"
                            v-model="expense.amount"
                            inputmode="decimal"
                            placeholder="0.00"
                            autofocus
                            :aria-invalid="expenseError !== ''"
                        />
                        <p v-if="expenseError" class="text-sm text-destructive">
                            {{ expenseError }}
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <Label for="expense-category">Category</Label>
                            <select
                                id="expense-category"
                                v-model="expense.categoryId"
                                :class="selectClass"
                            >
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="expense-date">Date</Label>
                            <Input
                                id="expense-date"
                                v-model="expense.paidOn"
                                type="date"
                            />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="expense-note">Note</Label>
                        <Input
                            id="expense-note"
                            v-model="expense.note"
                            placeholder="Loblaws"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="expense-method">Paid from</Label>
                        <select
                            id="expense-method"
                            v-model="expense.paymentMethodId"
                            :class="selectClass"
                        >
                            <option value="">Not recorded</option>
                            <option
                                v-for="method in paymentMethods"
                                :key="method.id"
                                :value="method.id"
                            >
                                {{ method.name }}
                            </option>
                        </select>
                    </div>
                    <DialogFooter>
                        <Button type="submit">Add expense</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
