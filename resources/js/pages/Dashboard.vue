<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { CalendarCheck } from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);

const hour = new Date().getHours();
const greeting =
    hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
</script>

<template>
    <div class="space-y-6">
        <Head title="Home" />

        <header class="space-y-1">
            <p v-if="page.props.house" class="text-sm text-muted-foreground">
                {{ page.props.house.name }}
            </p>
            <h1 class="text-2xl font-semibold tracking-tight md:text-3xl">
                {{ greeting }}, {{ firstName }}
            </h1>
        </header>

        <div
            class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-border bg-card px-6 py-16 text-center"
        >
            <span
                class="flex size-12 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground"
            >
                <CalendarCheck class="size-5" />
            </span>
            <p class="max-w-sm text-sm text-muted-foreground">
                Your agenda of bills, tasks and appointments will show up here.
            </p>
        </div>
    </div>
</template>
