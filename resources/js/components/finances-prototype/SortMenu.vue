<script setup lang="ts">
/**
 * PROTOTYPE sort control for a list header: "Date ↓" opens a menu of sort
 * keys. Picking the current key again flips the direction.
 */
import { ArrowDown, ArrowUp } from '@lucide/vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Direction, SortKey } from './store';

const sortKey = defineModel<SortKey>('sortKey', { required: true });
const direction = defineModel<Direction>('direction', { required: true });
const props = defineProps<{ dateDirection: Direction }>();

const labels: Record<SortKey, string> = {
    date: 'Date',
    amount: 'Amount',
    category: 'Category',
    name: 'Name',
};

/** Natural first direction for each key. */
const defaults = (key: SortKey): Direction =>
    key === 'date' ? props.dateDirection : key === 'amount' ? 'desc' : 'asc';

function pick(key: SortKey): void {
    if (key === sortKey.value) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';

        return;
    }

    sortKey.value = key;
    direction.value = defaults(key);
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="flex items-center gap-1 rounded-full px-2 py-1 text-xs text-muted-foreground hover:bg-accent hover:text-foreground"
                :aria-label="`Sorted by ${labels[sortKey]}, ${direction === 'asc' ? 'ascending' : 'descending'}`"
            >
                {{ labels[sortKey] }}
                <ArrowUp v-if="direction === 'asc'" class="size-3.5" />
                <ArrowDown v-else class="size-3.5" />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-40">
            <DropdownMenuLabel class="text-xs text-muted-foreground">
                Sort by · tap again to flip
            </DropdownMenuLabel>
            <DropdownMenuRadioGroup :model-value="sortKey">
                <DropdownMenuRadioItem
                    v-for="(label, key) in labels"
                    :key="key"
                    :value="key"
                    @select="pick(key)"
                >
                    {{ label }}
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
