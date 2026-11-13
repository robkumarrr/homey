<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import CommitmentAmountController from '@/actions/App/Http/Controllers/Finances/CommitmentAmountController';
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
import { centsToInput, formatDay } from '@/lib/money';
import type { Commitment, Occurrence } from '@/types';

/**
 * Sets what one due date of a bill costs: for just that date, or (for a
 * fixed bill) from that date on. Earlier dates never change.
 */
const target = defineModel<{ bill: Commitment; occurrence: Occurrence } | null>(
    'target',
    { required: true },
);

const open = computed({
    get: () => target.value !== null,
    set: (isOpen) => !isOpen && (target.value = null),
});

const scope = ref<'month' | 'onward'>('month');
watch(target, () => (scope.value = 'month'));
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="p-5 sm:max-w-sm sm:p-6">
            <template v-if="target">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            target.occurrence.amount_cents === null
                                ? 'Enter amount'
                                : 'Change amount'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ target.bill.name }} · due
                        {{ formatDay(target.occurrence.due_on) }}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    v-bind="
                        CommitmentAmountController.update.form(target.bill.id)
                    "
                    :options="{ preserveScroll: true }"
                    class="grid gap-4"
                    v-slot="{ errors, processing }"
                    @success="target = null"
                >
                    <input
                        type="hidden"
                        name="due_on"
                        :value="target.occurrence.due_on"
                    />
                    <!-- From this date on replaces any amount set for just this date. -->
                    <input
                        v-if="scope === 'onward'"
                        type="hidden"
                        name="month_amount"
                        value=""
                    />

                    <div class="grid gap-2">
                        <Label for="occurrence-amount">Amount</Label>
                        <Input
                            id="occurrence-amount"
                            :name="
                                scope === 'month'
                                    ? 'month_amount'
                                    : 'onward_amount'
                            "
                            inputmode="decimal"
                            placeholder="0.00"
                            autocomplete="off"
                            required
                            :default-value="
                                target.occurrence.amount_cents === null
                                    ? ''
                                    : centsToInput(
                                          target.occurrence.amount_cents,
                                      )
                            "
                            :aria-invalid="
                                !!(errors.month_amount ?? errors.onward_amount)
                            "
                        />
                        <InputError
                            :message="
                                errors.month_amount ??
                                errors.onward_amount ??
                                errors.due_on
                            "
                        />
                    </div>

                    <fieldset
                        v-if="!target.bill.amount_varies"
                        class="grid gap-2"
                    >
                        <legend class="sr-only">Applies to</legend>
                        <label
                            v-for="option in [
                                { value: 'month', label: 'Just this month' },
                                {
                                    value: 'onward',
                                    label: 'This and later months',
                                },
                            ] as const"
                            :key="option.value"
                            class="flex items-center gap-2 text-sm"
                            :data-test="`scope-${option.value}`"
                        >
                            <input
                                v-model="scope"
                                type="radio"
                                :value="option.value"
                                class="size-4 accent-primary"
                            />
                            {{ option.label }}
                        </label>
                        <p class="text-xs text-muted-foreground">
                            Months before
                            {{ formatDay(target.occurrence.due_on) }} keep their
                            amount.
                        </p>
                    </fieldset>

                    <DialogFooter>
                        <Button
                            type="submit"
                            :disabled="processing"
                            data-test="save-amount"
                        >
                            Save amount
                        </Button>
                    </DialogFooter>
                </Form>
            </template>
        </DialogContent>
    </Dialog>
</template>
