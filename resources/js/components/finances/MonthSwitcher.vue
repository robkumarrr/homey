<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { finances } from '@/routes';

/** "This month" sits to the left and keeps its space when hidden, so the arrows never move. */
const props = defineProps<{ month: string }>();

const shift = (step: number): string => {
    const [year, month] = props.month.split('-').map(Number);
    const date = new Date(year, month - 1 + step, 1);

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
};

const now = new Date();
const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
const isCurrentMonth = computed(() => props.month === currentMonth);

const label = computed(() => {
    const [year, month] = props.month.split('-').map(Number);

    return new Intl.DateTimeFormat('en-CA', {
        month: 'long',
        year: 'numeric',
    }).format(new Date(year, month - 1, 1));
});

const linkOptions = { preserveScroll: true, preserveState: true } as const;
</script>

<template>
    <div class="flex items-center gap-1">
        <Link
            :href="finances()"
            v-bind="linkOptions"
            class="rounded-full px-2.5 py-1 text-xs text-muted-foreground hover:bg-accent hover:text-foreground"
            :class="isCurrentMonth && 'invisible'"
            :tabindex="isCurrentMonth ? -1 : 0"
        >
            This month
        </Link>
        <Link
            :href="finances({ query: { month: shift(-1) } })"
            v-bind="linkOptions"
            class="rounded-full p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground"
            aria-label="Previous month"
        >
            <ChevronLeft class="size-4" />
        </Link>
        <span class="w-32 text-center text-sm font-medium">{{ label }}</span>
        <Link
            :href="finances({ query: { month: shift(1) } })"
            v-bind="linkOptions"
            class="rounded-full p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground"
            aria-label="Next month"
        >
            <ChevronRight class="size-4" />
        </Link>
    </div>
</template>
