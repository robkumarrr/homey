<script setup lang="ts">
/**
 * PROTOTYPE — three structurally different home screens on the existing
 * /dashboard route, switchable via ?variant=A|B|C|D and the floating bar.
 *
 * Question: what should the home screen lead with — time (A), money (B) or
 * place (C)? Fixture data only, candidate forest/cream palette on all three.
 * Throwaway: lives on the prototype/home-screen branch, never main.
 */
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PrototypeShell from '@/components/home-prototype/PrototypeShell.vue';
import VariantA from '@/components/home-prototype/VariantA.vue';
import VariantB from '@/components/home-prototype/VariantB.vue';
import VariantC from '@/components/home-prototype/VariantC.vue';
import VariantD from '@/components/home-prototype/VariantD.vue';
import PrototypeSwitcher from '@/components/PrototypeSwitcher.vue';

defineOptions({ layout: PrototypeShell });

const variants = [
    { key: 'A', label: 'Agenda (time first)' },
    { key: 'B', label: 'Money first' },
    { key: 'C', label: 'Areas (place first)' },
    { key: 'D', label: 'Hybrid (A + C)' },
];

const page = usePage();

const current = computed(() => {
    const requested = new URL(page.url, 'http://localhost').searchParams
        .get('variant')
        ?.toUpperCase();

    return variants.some((variant) => variant.key === requested)
        ? (requested as string)
        : 'A';
});
</script>

<template>
    <div>
        <Head title="Home" />

        <VariantA v-if="current === 'A'" />
        <VariantB v-else-if="current === 'B'" />
        <VariantC v-else-if="current === 'C'" />
        <VariantD v-else />

        <PrototypeSwitcher :variants="variants" :current="current" />
    </div>
</template>
