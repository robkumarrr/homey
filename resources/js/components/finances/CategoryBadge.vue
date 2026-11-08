<script setup lang="ts">
import {
    Car,
    CircleEllipsis,
    Hammer,
    House,
    PawPrint,
    ShoppingCart,
    Tv,
    UtensilsCrossed,
    Zap,
} from '@lucide/vue';
import type { Component } from 'vue';
import type { Category, CategoryColour, CategoryIcon } from '@/types';

withDefaults(defineProps<{ category: Category; size?: 'sm' | 'md' }>(), {
    size: 'md',
});

const icons: Record<CategoryIcon, Component> = {
    house: House,
    'shopping-cart': ShoppingCart,
    'utensils-crossed': UtensilsCrossed,
    car: Car,
    zap: Zap,
    tv: Tv,
    'paw-print': PawPrint,
    hammer: Hammer,
    'circle-ellipsis': CircleEllipsis,
};

/** Written out in full so Tailwind can see every class. */
const tones: Record<CategoryColour, string> = {
    blue: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    emerald:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    pink: 'bg-pink-100 text-pink-700 dark:bg-pink-950 dark:text-pink-300',
    sky: 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
    yellow: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-300',
    violet: 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
    teal: 'bg-teal-100 text-teal-700 dark:bg-teal-950 dark:text-teal-300',
    amber: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    zinc: 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
};
</script>

<template>
    <span
        class="flex shrink-0 items-center justify-center rounded-full"
        :class="[tones[category.colour], size === 'sm' ? 'size-6' : 'size-8']"
        :title="category.name"
    >
        <component
            :is="icons[category.icon]"
            :class="size === 'sm' ? 'size-3.5' : 'size-4'"
            aria-hidden="true"
        />
    </span>
</template>
