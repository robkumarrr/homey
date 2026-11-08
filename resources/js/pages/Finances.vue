<script setup lang="ts">
/**
 * PROTOTYPE: three Finances variants on the real /finances route, switched
 * with ?variant=A|B|C. All share one in-memory store, so adding an expense or
 * marking a bill paid in one variant shows up in the others. Reload resets.
 */
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FinanceDialogs from '@/components/finances-prototype/FinanceDialogs.vue';
import { state } from '@/components/finances-prototype/store';
import VariantA from '@/components/finances-prototype/VariantA.vue';
import VariantB from '@/components/finances-prototype/VariantB.vue';
import VariantC from '@/components/finances-prototype/VariantC.vue';
import PrototypeSwitcher from '@/components/PrototypeSwitcher.vue';

const variants = [
    { key: 'A', label: 'Ledger' },
    { key: 'B', label: 'Statement' },
    { key: 'C', label: 'Categories' },
];

const page = usePage();
const variant = computed(
    () =>
        new URLSearchParams(page.url.split('?')[1] ?? '').get('variant') ?? 'A',
);
</script>

<template>
    <div class="space-y-8">
        <Head title="Finances" />

        <VariantA v-if="variant === 'A'" />
        <VariantB v-else-if="variant === 'B'" />
        <VariantC v-else />

        <FinanceDialogs />

        <details
            class="rounded-xl border border-dashed p-3 text-xs text-muted-foreground"
        >
            <summary class="cursor-pointer">
                Prototype state · {{ state.expenses.length }} expenses ·
                {{ state.skipped.size }} skipped · last action:
                {{ state.log[0] ?? 'none yet' }}
            </summary>
            <ul class="mt-2 space-y-0.5">
                <li v-for="(line, index) in state.log" :key="index">
                    {{ line }}
                </li>
            </ul>
        </details>

        <PrototypeSwitcher :variants="variants" :current="variant" />
    </div>
</template>
