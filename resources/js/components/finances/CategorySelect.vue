<script setup lang="ts">
import { computed } from 'vue';
import type { Category } from '@/types';

/** The category picker. Archived categories only show when `keepId` already uses one. */
const props = defineProps<{
    id: string;
    categories: Category[];
    keepId?: number;
    invalid?: boolean;
}>();

const selectable = computed(() =>
    props.categories.filter(
        (category) => !category.archived || category.id === props.keepId,
    ),
);

const selectedId = computed(
    () =>
        props.keepId ??
        selectable.value.find((c) => c.name === 'Groceries')?.id ??
        selectable.value[0]?.id,
);
</script>

<template>
    <select
        :id="id"
        name="category_id"
        class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
        :aria-invalid="invalid"
    >
        <option
            v-for="category in selectable"
            :key="category.id"
            :value="category.id"
            :selected="category.id === selectedId"
        >
            {{ category.name }}
        </option>
    </select>
</template>
