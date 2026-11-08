<script setup lang="ts">
import { ArrowDown, ArrowUp } from '@lucide/vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { SortDirection, SortKey } from '@/types';

/** "Date ↓" opens a menu of sort keys; picking the current key again flips the direction. */
const sortKey = defineModel<SortKey>('sortKey', { required: true });
const direction = defineModel<SortDirection>('direction', { required: true });
const props = defineProps<{ dateDirection: SortDirection }>();

const labels: Record<SortKey, string> = {
    date: 'Date',
    amount: 'Amount',
    category: 'Category',
    name: 'Name',
};

const naturalDirection = (key: SortKey): SortDirection =>
    key === 'date' ? props.dateDirection : key === 'amount' ? 'desc' : 'asc';

function pick(key: SortKey): void {
    if (key === sortKey.value) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';

        return;
    }

    sortKey.value = key;
    direction.value = naturalDirection(key);
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="flex h-7 items-center gap-1 rounded-md border bg-background px-2 text-xs text-muted-foreground shadow-xs hover:bg-accent hover:text-foreground dark:border-input dark:bg-input/30"
                :aria-label="`Sorted by ${labels[sortKey]}, ${direction === 'asc' ? 'ascending' : 'descending'}`"
            >
                {{ labels[sortKey] }}
                <ArrowUp v-if="direction === 'asc'" class="size-3.5" />
                <ArrowDown v-else class="size-3.5" />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-44">
            <DropdownMenuLabel
                class="text-xs font-normal text-muted-foreground"
            >
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
