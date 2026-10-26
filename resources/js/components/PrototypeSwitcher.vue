<script setup lang="ts">
/**
 * PROTOTYPE variant switcher. Floating pill that cycles ?variant= on the
 * current route (arrows or ← / →). Dev builds only, so a stray merge can't
 * ship it to users.
 */
import { router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted } from 'vue';

const props = defineProps<{
    variants: { key: string; label: string }[];
    current: string;
}>();

const isDevBuild = import.meta.env.DEV;

const index = computed(() =>
    Math.max(
        0,
        props.variants.findIndex((variant) => variant.key === props.current),
    ),
);

const currentVariant = computed(() => props.variants[index.value]);

function cycle(step: number): void {
    const next =
        props.variants[
            (index.value + step + props.variants.length) % props.variants.length
        ];
    const url = new URL(window.location.href);
    url.searchParams.set('variant', next.key);

    router.replace({
        url: url.pathname + url.search,
        preserveState: true,
        preserveScroll: true,
    });
}

function onKeydown(event: KeyboardEvent): void {
    if (event.metaKey || event.ctrlKey || event.altKey) {
        return;
    }

    const target = event.target as HTMLElement | null;

    if (
        target?.closest(
            'input, textarea, select, [contenteditable=""], [contenteditable="true"]',
        )
    ) {
        return;
    }

    if (event.key === 'ArrowLeft') {
        cycle(-1);
    } else if (event.key === 'ArrowRight') {
        cycle(1);
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div
        v-if="isDevBuild"
        class="pointer-events-none fixed inset-x-0 bottom-[calc(4.75rem+env(safe-area-inset-bottom))] z-50 flex justify-center md:bottom-6"
    >
        <div
            class="pointer-events-auto flex items-center gap-1 rounded-full bg-neutral-950 p-1.5 text-white shadow-xl ring-1 ring-white/15 dark:bg-white dark:text-neutral-950 dark:ring-black/10"
        >
            <button
                type="button"
                class="rounded-full p-1.5 hover:bg-white/15 dark:hover:bg-black/10"
                aria-label="Previous variant"
                @click="cycle(-1)"
            >
                <ChevronLeft class="size-4" />
            </button>
            <span class="min-w-44 px-1 text-center text-xs font-medium">
                <span class="opacity-60">Prototype</span> ·
                {{ currentVariant.key }} — {{ currentVariant.label }}
            </span>
            <button
                type="button"
                class="rounded-full p-1.5 hover:bg-white/15 dark:hover:bg-black/10"
                aria-label="Next variant"
                @click="cycle(1)"
            >
                <ChevronRight class="size-4" />
            </button>
        </div>
    </div>
</template>
