<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PaymentMethodController from '@/actions/App/Http/Controllers/Settings/PaymentMethodController';
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
import { paymentMethodKinds } from '@/lib/paymentMethods';
import type { PaymentMethod, PaymentMethodKind } from '@/types';

/** Add a new payment method, or edit `paymentMethod` when one is given. */
const props = defineProps<{ paymentMethod: PaymentMethod | null }>();

const open = defineModel<boolean>('open', { required: true });

const kind = ref<PaymentMethodKind>('credit_card');

/** Remount the form on every open so its defaults are fresh. */
const formKey = ref(0);
watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    formKey.value++;
    kind.value = props.paymentMethod?.kind ?? 'credit_card';
});

const kinds = Object.keys(paymentMethodKinds) as PaymentMethodKind[];
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="max-h-[calc(100dvh-2rem)] overflow-y-auto p-5 sm:max-w-md sm:p-6"
        >
            <DialogHeader>
                <DialogTitle>
                    {{
                        paymentMethod
                            ? 'Edit payment method'
                            : 'Add payment method'
                    }}
                </DialogTitle>
                <DialogDescription>
                    A card or account you pay from. Homey never stores the full
                    number.
                </DialogDescription>
            </DialogHeader>

            <Form
                :key="formKey"
                v-bind="
                    paymentMethod
                        ? PaymentMethodController.update.form(paymentMethod.id)
                        : PaymentMethodController.store.form()
                "
                :options="{ preserveScroll: true }"
                class="grid gap-4"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <fieldset class="grid gap-2">
                    <legend class="mb-2 text-sm font-medium">Kind</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <label
                            v-for="option in kinds"
                            :key="option"
                            class="cursor-pointer"
                            :data-test="`kind-${option}`"
                        >
                            <input
                                v-model="kind"
                                type="radio"
                                name="kind"
                                :value="option"
                                class="peer sr-only"
                            />
                            <span
                                class="flex items-center justify-center gap-2 rounded-md border px-3 py-2 text-sm text-muted-foreground peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-foreground peer-focus-visible:ring-[3px] peer-focus-visible:ring-ring/50"
                            >
                                <component
                                    :is="paymentMethodKinds[option].icon"
                                    class="size-4"
                                    aria-hidden="true"
                                />
                                {{ paymentMethodKinds[option].label }}
                            </span>
                        </label>
                    </div>
                    <InputError :message="errors.kind" />
                </fieldset>

                <div class="grid grid-cols-[1fr_7rem] gap-3 [&>*]:min-w-0">
                    <div class="grid gap-2">
                        <Label for="payment-method-name">Nickname</Label>
                        <Input
                            id="payment-method-name"
                            name="name"
                            :placeholder="
                                kind === 'credit_card'
                                    ? 'TD Visa'
                                    : 'TD chequing'
                            "
                            autocomplete="off"
                            required
                            :default-value="paymentMethod?.name ?? ''"
                            :aria-invalid="!!errors.name"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="payment-method-last-four">Last 4</Label>
                        <Input
                            id="payment-method-last-four"
                            name="last_four"
                            inputmode="numeric"
                            maxlength="4"
                            placeholder="1234"
                            autocomplete="off"
                            :default-value="paymentMethod?.last_four ?? ''"
                            :aria-invalid="!!errors.last_four"
                        />
                    </div>
                </div>
                <InputError :message="errors.name" />
                <InputError :message="errors.last_four" />

                <DialogFooter>
                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="save-payment-method"
                    >
                        {{
                            paymentMethod
                                ? 'Save changes'
                                : 'Add payment method'
                        }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
