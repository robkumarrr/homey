<script setup lang="ts">
import { PawPrint } from '@lucide/vue';
import TabPlaceholder from '@/components/TabPlaceholder.vue';
</script>

<template>
    <TabPlaceholder
        title="Pets"
        description="Your pets, their vets and appointments will live here."
        :icon="PawPrint"
    />
</template>
