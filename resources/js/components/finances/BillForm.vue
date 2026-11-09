<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CommitmentController from '@/actions/App/Http/Controllers/Finances/CommitmentController';
import CategorySelect from '@/components/finances/CategorySelect.vue';
import PaymentMethodSelect from '@/components/finances/PaymentMethodSelect.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { todayIso } from '@/lib/money';
import { repeatOptionFor, repeatOptions } from '@/lib/schedule';
import type { Category, Commitment, PaymentMethod } from '@/types';

/** Add a new bill, or edit `bill` when one is given. */
const props = defineProps<{
    bill: Commitment | null;
    categories: Category[];
    paymentMethods: PaymentMethod[];
}>();

const emit = defineEmits<{ saved: [] }>();

const amountVaries = ref(props.bill?.amount_varies ?? false);

const repeatKey = ref(
    props.bill
        ? repeatOptionFor(props.bill.frequency, props.bill.interval).key
        : 'monthly',
);
const repeat = computed(() =>
    repeatOptions.find((option) => option.key === repeatKey.value)!,
);
</script>

<template>
    <Form
        v-bind="
            bill
                ? CommitmentController.update.form(bill.id)
                : CommitmentController.store.form()
        "
        :options="{ preserveScroll: true }"
        class="grid content-start gap-4"
        v-slot="{ errors, processing }"
        @success="emit('saved')"
    >
        <div class="grid gap-2">
            <Label for="bill-name">Name</Label>
            <Input
                id="bill-name"
                name="name"
                placeholder="Netflix"
                autocomplete="off"
                required
                :default-value="bill?.name ?? ''"
                :aria-invalid="!!errors.name"
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-3 [&>*]:min-w-0" :class="!bill && 'grid-cols-2'">
            <div v-if="!bill" class="grid gap-2">
                <Label for="bill-amount">
                    {{ amountVaries ? 'First bill' : 'Amount' }}
                    <span
                        v-if="amountVaries"
                        class="font-normal text-muted-foreground"
                        >(optional)</span
                    >
                </Label>
                <Input
                    id="bill-amount"
                    name="amount"
                    inputmode="decimal"
                    placeholder="0.00"
                    autocomplete="off"
                    :aria-invalid="!!errors.amount"
                />
            </div>
            <div class="grid gap-2">
                <Label for="bill-category">Category</Label>
                <CategorySelect
                    id="bill-category"
                    :categories="categories"
                    :keep-id="bill?.category_id"
                    :invalid="!!errors.category_id"
                />
            </div>
        </div>
        <InputError :message="errors.amount" />
        <InputError :message="errors.category_id" />

        <label class="-mt-1 flex items-start gap-2 text-sm">
            <input
                id="bill-amount-varies"
                v-model="amountVaries"
                type="checkbox"
                name="amount_varies"
                value="1"
                class="mt-0.5 size-4 shrink-0 rounded border-input accent-primary"
            />
            <span>
                Amount changes each time
                <span class="text-muted-foreground">(enter it each month)</span>
            </span>
        </label>
        <p v-if="bill" class="-mt-2 text-xs text-muted-foreground">
            To change an amount, use that month's ⋯ menu in Recurring. Months
            before it keep theirs.
        </p>

        <div class="grid grid-cols-2 gap-3 [&>*]:min-w-0">
            <div class="grid gap-2">
                <Label for="bill-repeats">Repeats</Label>
                <select
                    id="bill-repeats"
                    v-model="repeatKey"
                    class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
                >
                    <option
                        v-for="option in repeatOptions"
                        :key="option.key"
                        :value="option.key"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <input
                    type="hidden"
                    name="frequency"
                    :value="repeat.frequency"
                />
                <input type="hidden" name="interval" :value="repeat.interval" />
            </div>
            <div class="grid gap-2">
                <Label for="bill-starts-on">
                    {{ repeat.frequency === 'once' ? 'Due' : 'First due' }}
                </Label>
                <Input
                    id="bill-starts-on"
                    name="starts_on"
                    type="date"
                    :default-value="bill?.starts_on ?? todayIso()"
                    :aria-invalid="!!errors.starts_on"
                />
            </div>
        </div>
        <InputError :message="errors.frequency ?? errors.interval" />
        <InputError :message="errors.starts_on" />

        <div v-if="repeat.frequency !== 'once'" class="grid gap-2">
            <Label for="bill-ends-on">
                Final payment date
                <span class="font-normal text-muted-foreground"
                    >(optional)</span
                >
            </Label>
            <Input
                id="bill-ends-on"
                name="ends_on"
                type="date"
                :default-value="bill?.ends_on ?? ''"
                :aria-invalid="!!errors.ends_on"
            />
            <InputError :message="errors.ends_on" />
        </div>

        <PaymentMethodSelect
            id="bill-payment-method"
            :payment-methods="paymentMethods"
            :keep-id="bill?.payment_method_id"
            :error="errors.payment_method_id"
        />

        <DialogFooter>
            <Button type="submit" :disabled="processing" data-test="save-bill">
                {{ bill ? 'Save changes' : 'Add bill' }}
            </Button>
        </DialogFooter>
    </Form>
</template>
