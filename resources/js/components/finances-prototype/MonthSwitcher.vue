<script setup lang="ts">
/**
 * PROTOTYPE month switcher shared by the Finances variants. "This month" sits
 * to the left and keeps its space when hidden, so the arrows never move.
 */
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { isCurrentMonth, monthLabel, shiftMonth, state } from './store';
</script>

<template>
    <div class="flex items-center gap-1">
        <button
            type="button"
            class="rounded-full px-2.5 py-1 text-xs text-muted-foreground hover:bg-accent hover:text-foreground"
            :class="isCurrentMonth && 'invisible'"
            :tabindex="isCurrentMonth ? -1 : 0"
            @click="state.monthOffset = 0"
        >
            This month
        </button>
        <button
            type="button"
            class="rounded-full p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground disabled:opacity-30"
            aria-label="Previous month"
            :disabled="state.monthOffset <= -3"
            @click="shiftMonth(-1)"
        >
            <ChevronLeft class="size-4" />
        </button>
        <span class="w-32 text-center text-sm font-medium">
            {{ monthLabel }}
        </span>
        <button
            type="button"
            class="rounded-full p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground disabled:opacity-30"
            aria-label="Next month"
            :disabled="state.monthOffset >= 2"
            @click="shiftMonth(1)"
        >
            <ChevronRight class="size-4" />
        </button>
    </div>
</template>
