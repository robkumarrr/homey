<script setup lang="ts">
import { Wallet } from '@lucide/vue';
import TabPlaceholder from '@/components/TabPlaceholder.vue';
</script>

<template>
    <TabPlaceholder
        title="Finances"
        description="Bills, subscriptions and spending will live here."
        :icon="Wallet"
    />
</template>
