<script setup lang="ts">
import { categoryIcons, categoryTones } from '@/lib/categories';
import type { CategoryColour, CategoryIcon } from '@/types';

withDefaults(
    defineProps<{
        category: { name: string; icon: CategoryIcon; colour: CategoryColour };
        size?: 'sm' | 'md';
    }>(),
    { size: 'md' },
);
</script>

<template>
    <span
        class="flex shrink-0 items-center justify-center rounded-full"
        :class="[
            categoryTones[category.colour],
            size === 'sm' ? 'size-6' : 'size-8',
        ]"
        :title="category.name"
    >
        <component
            :is="categoryIcons[category.icon]"
            :class="size === 'sm' ? 'size-3.5' : 'size-4'"
            aria-hidden="true"
        />
    </span>
</template>
