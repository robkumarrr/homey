<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

defineProps<{
    title: string;
    description: string;
    icon: LucideIcon;
}>();
</script>

<template>
    <div class="space-y-6">
        <Head :title="title" />

        <h1 class="text-2xl font-semibold tracking-tight md:text-3xl">
            {{ title }}
        </h1>

        <div
            class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-border bg-card px-6 py-16 text-center"
        >
            <span
                class="flex size-12 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground"
            >
                <component :is="icon" class="size-5" />
            </span>
            <p class="max-w-sm text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
    </div>
</template>
