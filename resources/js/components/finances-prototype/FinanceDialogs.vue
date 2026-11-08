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
import ContactSelect from './ContactSelect.vue';
import { formatDay, money, parseCents } from './format';
import {
    addBill,
    addExpense,
    addingExpense,
    addMode,
    categories,
    confirmingDelete,
    deleteExpense,
    editing,
    lastPaidCents,
    payOccurrence,
    paying,
    paymentMethods,
    repeatLabels,
    today,
    updateExpense,
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
    paidToId: '',
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
    contactId: '',
});
const bill = ref(blankBill());

const addError = ref<{ field: string; message: string } | null>(null);

watch(addingExpense, (open) => {
    if (!open) {
        return;
    }

    const current = editing.value;
    expense.value = current
        ? {
              amount: (current.amountCents / 100).toFixed(2),
              paidOn: current.paidOn,
              categoryId: current.categoryId,
              note: current.note,
              paymentMethodId: current.paymentMethodId ?? '',
              paidToId: current.paidToId ?? '',
          }
        : blankExpense();
    bill.value = blankBill();
    addError.value = null;
});

function confirmDelete(): void {
    deleteExpense(confirmingDelete.value!);
    confirmingDelete.value = null;
}

watch(addMode, () => (addError.value = null));

function submitAdd(): void {
    if (addMode.value === 'expense') {
        const cents = parseCents(expense.value.amount);

        if (!cents) {
            addError.value = { field: 'amount', message: amountError };

            return;
        }

        const fields = {
            amountCents: cents,
            paidOn: expense.value.paidOn,
            categoryId: expense.value.categoryId,
            note: expense.value.note,
            paymentMethodId: expense.value.paymentMethodId || null,
            paidToId: expense.value.paidToId || null,
        };

        if (editing.value) {
            updateExpense(editing.value, fields);
        } else {
            addExpense(fields);
        }
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
            contactId: bill.value.contactId || undefined,
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
            <DialogContent
                v-if="paying"
                class="max-h-[calc(100dvh-2rem)] overflow-y-auto p-5 sm:max-w-sm sm:p-6"
            >
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
            <DialogContent
                class="max-h-[calc(100dvh-2rem)] overflow-y-auto p-5 sm:max-w-md sm:p-6"
            >
                <DialogHeader>
                    <DialogTitle>{{
                        editing ? 'Edit expense' : 'Add'
                    }}</DialogTitle>
                    <DialogDescription>
                        {{
                            addMode === 'expense'
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
                        :class="addMode === 'bill' && 'translate-x-full'"
                        aria-hidden="true"
                    />
                    <button
                        v-for="mode in ['expense', 'bill'] as const"
                        :key="mode"
                        type="button"
                        role="tab"
                        :aria-selected="addMode === mode"
                        class="relative rounded-md py-1.5 text-sm capitalize transition-colors"
                        :class="
                            addMode === mode
                                ? 'font-medium text-foreground'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="addMode = mode"
                    >
                        {{ mode }}
                    </button>
                </div>

                <form class="grid gap-4" @submit.prevent="submitAdd">
                    <!-- Both panes share one grid cell, so the dialog keeps the taller one's height. -->
                    <div class="grid">
                        <div
                            class="grid content-start gap-4 transition-opacity duration-200 [grid-area:1/1]"
                            :class="
                                addMode !== 'expense' &&
                                'pointer-events-none opacity-0'
                            "
                            :inert="addMode !== 'expense'"
                        >
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
                            <div class="grid grid-cols-2 gap-3 [&>*]:min-w-0">
                                <div class="grid gap-2">
                                    <Label for="expense-category"
                                        >Category</Label
                                    >
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
                            <div class="grid gap-2">
                                <Label for="expense-paid-to"
                                    >Paid to (optional)</Label
                                >
                                <ContactSelect
                                    id="expense-paid-to"
                                    v-model="expense.paidToId"
                                    :select-class="selectClass"
                                    empty-label="No one in particular"
                                />
                            </div>
                        </div>

                        <div
                            class="grid content-start gap-4 transition-opacity duration-200 [grid-area:1/1]"
                            :class="
                                addMode !== 'bill' &&
                                'pointer-events-none opacity-0'
                            "
                            :inert="addMode !== 'bill'"
                        >
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
                                        bill.variable
                                            ? 'Typical amount'
                                            : 'Amount'
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
                            <div class="grid grid-cols-2 gap-3 [&>*]:min-w-0">
                                <div class="grid gap-2">
                                    <Label for="bill-repeat">Repeats</Label>
                                    <select
                                        id="bill-repeat"
                                        v-model.number="bill.everyMonths"
                                        :class="selectClass"
                                    >
                                        <option
                                            v-for="(
                                                label, months
                                            ) in repeatLabels"
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
                            <div class="grid grid-cols-2 gap-3 [&>*]:min-w-0">
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
                                <Label for="bill-contact"
                                    >Contact (optional)</Label
                                >
                                <ContactSelect
                                    id="bill-contact"
                                    v-model="bill.contactId"
                                    :select-class="selectClass"
                                    empty-label="None"
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
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="submit">
                            {{ editing ? 'Save changes' : `Add ${addMode}` }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="confirmingDelete !== null"
            @update:open="(open) => !open && (confirmingDelete = null)"
        >
            <DialogContent
                v-if="confirmingDelete"
                class="max-h-[calc(100dvh-2rem)] overflow-y-auto p-5 sm:max-w-sm sm:p-6"
            >
                <DialogHeader>
                    <DialogTitle>Delete this expense?</DialogTitle>
                    <DialogDescription>
                        {{ confirmingDelete.note || 'Expense' }} ·
                        {{ money(confirmingDelete.amountCents) }} ·
                        {{ formatDay(confirmingDelete.paidOn) }}. You can undo
                        right after, or restore it later from Recently deleted.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="confirmingDelete = null">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="confirmDelete">
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
