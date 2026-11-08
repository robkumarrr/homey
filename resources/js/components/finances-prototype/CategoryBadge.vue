<script setup lang="ts">
/** PROTOTYPE category badge: the category's icon on its colour tone. */
import { computed } from 'vue';
import { category } from './store';

const props = withDefaults(
    defineProps<{ categoryId: string; size?: 'sm' | 'md' }>(),
    { size: 'md' },
);

const current = computed(() => category(props.categoryId));
</script>

<template>
    <span
        class="flex shrink-0 items-center justify-center rounded-full"
        :class="[current.tone, size === 'sm' ? 'size-6' : 'size-8']"
        :title="current.name"
    >
        <component
            :is="current.icon"
            :class="size === 'sm' ? 'size-3.5' : 'size-4'"
            aria-hidden="true"
        />
    </span>
</template>
