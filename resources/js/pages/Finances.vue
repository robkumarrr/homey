<script setup lang="ts">
/**
 * PROTOTYPE: eight Finances variants on the real /finances route, switched
 * with ?variant=A–H (H is the default). All share one in-memory store, so adding an expense or
 * marking a bill paid in one variant shows up in the others. Reload resets.
 */
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FinanceDialogs from '@/components/finances-prototype/FinanceDialogs.vue';
import { state } from '@/components/finances-prototype/store';
import VariantA from '@/components/finances-prototype/VariantA.vue';
import VariantB from '@/components/finances-prototype/VariantB.vue';
import VariantC from '@/components/finances-prototype/VariantC.vue';
import VariantD from '@/components/finances-prototype/VariantD.vue';
import VariantE from '@/components/finances-prototype/VariantE.vue';
import VariantF from '@/components/finances-prototype/VariantF.vue';
import VariantG from '@/components/finances-prototype/VariantG.vue';
import VariantH from '@/components/finances-prototype/VariantH.vue';
import PrototypeSwitcher from '@/components/PrototypeSwitcher.vue';

const variants = [
    { key: 'A', label: 'Ledger' },
    { key: 'B', label: 'Statement' },
    { key: 'C', label: 'Categories' },
    { key: 'D', label: 'Ledger v2' },
    { key: 'E', label: 'Ledger v3' },
    { key: 'F', label: 'Ledger v4' },
    { key: 'G', label: 'Ledger v5' },
    { key: 'H', label: 'Ledger v6' },
];

const page = usePage();
const variant = computed(
    () =>
        new URLSearchParams(page.url.split('?')[1] ?? '').get('variant') ?? 'H',
);
</script>

<template>
    <div class="space-y-8">
        <Head title="Finances" />

        <VariantA v-if="variant === 'A'" />
        <VariantB v-else-if="variant === 'B'" />
        <VariantC v-else-if="variant === 'C'" />
        <VariantD v-else-if="variant === 'D'" />
        <VariantE v-else-if="variant === 'E'" />
        <VariantF v-else-if="variant === 'F'" />
        <VariantG v-else-if="variant === 'G'" />
        <VariantH v-else />

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
