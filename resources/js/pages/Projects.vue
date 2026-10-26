<script setup lang="ts">
import { Hammer } from '@lucide/vue';
import TabPlaceholder from '@/components/TabPlaceholder.vue';
</script>

<template>
    <TabPlaceholder
        title="Projects"
        description="Renovations and repairs, with their quotes and payments, will live here."
        :icon="Hammer"
    />
</template>
