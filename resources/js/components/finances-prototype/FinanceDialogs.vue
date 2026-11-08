<script setup lang="ts">
/**
 * PROTOTYPE dialogs shared by every Finances variant: the Paid form for a bill
 * occurrence, and one Add dialog with an Expense | Bill switch at the top.
 * All of them write to the in-memory store.
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
    addBill,
    addExpense,
    addingExpense,
    addMode,
    categories,
    lastPaidCents,
    payOccurrence,
    paying,
    paymentMethods,
    repeatLabels,
    today,
} from './store';
import type { Repeat } from './store';

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';

const amountError = 'Enter an amount like 19.99 or $1,200.';

/* Paid form */
const paid = ref({ amount: '', paidOn: today, paymentMethodId: '' });
const paidError = ref('');

watch(paying, (occurrence) => {
    if (!occurrence) {
        return;
    }

    paid.value = {
        amount: (lastPaidCents(occurrence.bill) / 100).toFixed(2),
        paidOn: today,
        paymentMethodId: occurrence.bill.paymentMethodId ?? '',
    };
    paidError.value = '';
});

function submitPaid(): void {
    const cents = parseCents(paid.value.amount);

    if (!cents) {
        paidError.value = amountError;

        return;
    }

    payOccurrence(paying.value!, {
        amountCents: cents,
        paidOn: paid.value.paidOn,
        paymentMethodId: paid.value.paymentMethodId || null,
    });
    paying.value = null;
}

/* Add: expense */
const blankExpense = () => ({
    amount: '',
    paidOn: today,
    categoryId: 'groceries',
    note: '',
    paymentMethodId: '',
});
const expense = ref(blankExpense());

/* Add: bill */
const blankBill = () => ({
    name: '',
    amount: '',
    variable: false,
    categoryId: 'utilities',
    everyMonths: 1 as Repeat,
    firstDueOn: today,
    paymentMethodId: '',
    autopay: false,
    contact: '',
});
const bill = ref(blankBill());

const addError = ref<{ field: string; message: string } | null>(null);

watch(addingExpense, (open) => {
    if (open) {
        expense.value = blankExpense();
        bill.value = blankBill();
        addError.value = null;
    }
});

watch(addMode, () => (addError.value = null));

function submitAdd(): void {
    if (addMode.value === 'expense') {
        const cents = parseCents(expense.value.amount);

        if (!cents) {
            addError.value = { field: 'amount', message: amountError };

            return;
        }

        addExpense({
            amountCents: cents,
            paidOn: expense.value.paidOn,
            categoryId: expense.value.categoryId,
            note: expense.value.note,
            paymentMethodId: expense.value.paymentMethodId || null,
        });
    } else {
        if (bill.value.name.trim() === '') {
            addError.value = {
                field: 'name',
                message: 'Give the bill a name.',
            };

            return;
        }

        const cents = parseCents(bill.value.amount);

        if (!cents) {
            addError.value = { field: 'amount', message: amountError };

            return;
        }

        addBill({
            name: bill.value.name.trim(),
            amountCents: cents,
            variable: bill.value.variable,
            categoryId: bill.value.categoryId,
            everyMonths: bill.value.everyMonths,
            firstDueOn: bill.value.firstDueOn,
            paymentMethodId: bill.value.paymentMethodId || null,
            autopay:
                bill.value.autopay &&
                !bill.value.variable &&
                bill.value.everyMonths !== 0,
            contact: bill.value.contact || undefined,
        });
    }

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
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add</DialogTitle>
                    <DialogDescription>
                        {{
                            addMode === 'expense'
                                ? 'Money that left the household.'
                                : 'Something you pay on a schedule, or once on a set date.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="grid grid-cols-2 gap-1 rounded-lg bg-muted p-1"
                    role="tablist"
                >
                    <button
                        v-for="mode in ['expense', 'bill'] as const"
                        :key="mode"
                        type="button"
                        role="tab"
                        :aria-selected="addMode === mode"
                        class="rounded-md py-1.5 text-sm capitalize"
                        :class="
                            addMode === mode
                                ? 'bg-card font-medium shadow-xs'
                                : 'text-muted-foreground'
                        "
                        @click="addMode = mode"
                    >
                        {{ mode }}
                    </button>
                </div>

                <form class="grid gap-4" @submit.prevent="submitAdd">
                    <template v-if="addMode === 'expense'">
                        <div class="grid gap-2">
                            <Label for="expense-amount">Amount</Label>
                            <Input
                                id="expense-amount"
                                v-model="expense.amount"
                                inputmode="decimal"
                                placeholder="0.00"
                                autofocus
                                :aria-invalid="addError?.field === 'amount'"
                            />
                            <p
                                v-if="addError?.field === 'amount'"
                                class="text-sm text-destructive"
                            >
                                {{ addError.message }}
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
                    </template>

                    <template v-else>
                        <div class="grid gap-2">
                            <Label for="bill-name">Name</Label>
                            <Input
                                id="bill-name"
                                v-model="bill.name"
                                placeholder="Home insurance"
                                :aria-invalid="addError?.field === 'name'"
                            />
                            <p
                                v-if="addError?.field === 'name'"
                                class="text-sm text-destructive"
                            >
                                {{ addError.message }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="bill-amount">
                                {{
                                    bill.variable ? 'Typical amount' : 'Amount'
                                }}
                            </Label>
                            <Input
                                id="bill-amount"
                                v-model="bill.amount"
                                inputmode="decimal"
                                placeholder="0.00"
                                :aria-invalid="addError?.field === 'amount'"
                            />
                            <p
                                v-if="addError?.field === 'amount'"
                                class="text-sm text-destructive"
                            >
                                {{ addError.message }}
                            </p>
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="bill.variable"
                                    type="checkbox"
                                />
                                The amount changes each time (hydro, gas)
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="bill-repeat">Repeats</Label>
                                <select
                                    id="bill-repeat"
                                    v-model.number="bill.everyMonths"
                                    :class="selectClass"
                                >
                                    <option
                                        v-for="(label, months) in repeatLabels"
                                        :key="months"
                                        :value="Number(months)"
                                    >
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="bill-first">
                                    {{
                                        bill.everyMonths === 0
                                            ? 'Due on'
                                            : 'Next due'
                                    }}
                                </Label>
                                <Input
                                    id="bill-first"
                                    v-model="bill.firstDueOn"
                                    type="date"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="bill-category">Category</Label>
                                <select
                                    id="bill-category"
                                    v-model="bill.categoryId"
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
                                <Label for="bill-method">Paid from</Label>
                                <select
                                    id="bill-method"
                                    v-model="bill.paymentMethodId"
                                    :class="selectClass"
                                >
                                    <option value="">Not set</option>
                                    <option
                                        v-for="method in paymentMethods"
                                        :key="method.id"
                                        :value="method.id"
                                    >
                                        {{ method.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="bill-contact">Contact (optional)</Label>
                            <Input
                                id="bill-contact"
                                v-model="bill.contact"
                                placeholder="Jane Ko · insurance broker"
                            />
                        </div>
                        <label
                            class="flex items-center gap-2 text-sm"
                            :class="
                                (bill.variable || bill.everyMonths === 0) &&
                                'opacity-50'
                            "
                        >
                            <input
                                v-model="bill.autopay"
                                type="checkbox"
                                :disabled="
                                    bill.variable || bill.everyMonths === 0
                                "
                            />
                            Autopay: record it as paid on each due date
                            <span class="text-xs text-muted-foreground">
                                (fixed, repeating bills only)
                            </span>
                        </label>
                    </template>

                    <DialogFooter>
                        <Button type="submit"> Add {{ addMode }} </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
