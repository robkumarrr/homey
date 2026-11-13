<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { index as paymentMethodsIndex } from '@/routes/payment-methods';
import type { PaymentMethod } from '@/types';

/**
 * The optional "Paid with" picker. Archived methods only show when `keepId`
 * already uses one; with no methods at all it points to Settings instead.
 */
const props = defineProps<{
    id: string;
    paymentMethods: PaymentMethod[];
    keepId?: number | null;
    error?: string;
}>();

const selectable = computed(() =>
    props.paymentMethods.filter(
        (method) => !method.archived || method.id === props.keepId,
    ),
);
</script>

<template>
    <div class="grid gap-2">
        <Label :for="id">
            Paid with
            <span class="font-normal text-muted-foreground">(optional)</span>
        </Label>
        <select
            v-if="selectable.length"
            :id="id"
            name="payment_method_id"
            class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
            :aria-invalid="!!error"
        >
            <option value="">Not recorded</option>
            <option
                v-for="method in selectable"
                :key="method.id"
                :value="method.id"
                :selected="method.id === keepId"
            >
                {{ method.name }}
            </option>
        </select>
        <p v-else class="text-sm text-muted-foreground">
            Add your cards and accounts in
            <Link
                :href="paymentMethodsIndex()"
                class="underline underline-offset-4"
                >Settings</Link
            >
            to record which one paid.
        </p>
        <InputError :message="error" />
    </div>
</template>
