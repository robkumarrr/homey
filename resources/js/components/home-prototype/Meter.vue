<script setup lang="ts">
/**
 * PROTOTYPE meter. Fill carries severity (on track -> close -> over); the track
 * is a lighter step of the same ramp; state always ships with an icon + label.
 */
import { CircleCheck, OctagonAlert, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import { money } from './format';

const props = withDefaults(
    defineProps<{
        valueCents: number;
        limitCents: number;
        showStatus?: boolean;
        progress?: boolean;
    }>(),
    { showStatus: false, progress: false },
);

const ratio = computed(() =>
    props.limitCents > 0 ? props.valueCents / props.limitCents : 0,
);

const state = computed<'ok' | 'warn' | 'over'>(() => {
    if (props.progress || ratio.value < 0.85) {
        return 'ok';
    }

    return ratio.value <= 1 ? 'warn' : 'over';
});

const tones = {
    ok: {
        fill: 'bg-(--meter-ok)',
        track: 'bg-(--meter-ok-track)',
        icon: 'text-(--meter-ok)',
    },
    warn: {
        fill: 'bg-(--meter-warn)',
        track: 'bg-(--meter-warn-track)',
        icon: 'text-(--meter-warn)',
    },
    over: {
        fill: 'bg-(--meter-over)',
        track: 'bg-(--meter-over-track)',
        icon: 'text-(--meter-over)',
    },
};

const status = computed(() => {
    if (state.value === 'over') {
        return {
            icon: OctagonAlert,
            label: `Over by ${money(props.valueCents - props.limitCents)}`,
        };
    }

    if (state.value === 'warn') {
        return { icon: TriangleAlert, label: 'Close to limit' };
    }

    return {
        icon: CircleCheck,
        label: props.progress
            ? `${Math.round(ratio.value * 100)}% paid`
            : 'On track',
    };
});
</script>

<template>
    <div class="space-y-1.5">
        <div
            class="h-2 w-full overflow-hidden rounded-full"
            :class="tones[state].track"
            role="meter"
            :aria-valuenow="Math.round(ratio * 100)"
            aria-valuemin="0"
            aria-valuemax="100"
            :aria-label="status.label"
        >
            <div
                class="h-full rounded-full"
                :class="tones[state].fill"
                :style="{ width: `${Math.min(ratio, 1) * 100}%` }"
            />
        </div>
        <p
            v-if="showStatus"
            class="flex items-center gap-1.5 text-xs text-muted-foreground"
        >
            <component
                :is="status.icon"
                class="size-3.5 shrink-0"
                :class="tones[state].icon"
            />
            {{ status.label }}
        </p>
    </div>
</template>
