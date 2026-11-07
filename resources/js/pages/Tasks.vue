<script setup lang="ts">
import { ListChecks } from '@lucide/vue';
import TabPlaceholder from '@/components/TabPlaceholder.vue';
</script>

<template>
    <TabPlaceholder
        title="Tasks"
        description="Recurring jobs for the house and pets will live here."
        :icon="ListChecks"
    />
</template>
