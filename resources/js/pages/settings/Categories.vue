<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Archive, Ellipsis, Pencil, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import CategoryController from '@/actions/App/Http/Controllers/Settings/CategoryController';
import CategoryBadge from '@/components/finances/CategoryBadge.vue';
import CategoryDialog from '@/components/finances/CategoryDialog.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Category } from '@/types';

const props = defineProps<{ categories: Category[] }>();

const active = computed(() => props.categories.filter((c) => !c.archived));
const archived = computed(() => props.categories.filter((c) => c.archived));

const dialogOpen = ref(false);
const editing = ref<Category | null>(null);

function openAdd(): void {
    editing.value = null;
    dialogOpen.value = true;
}

function openEdit(category: Category): void {
    editing.value = category;
    dialogOpen.value = true;
}

function archive(category: Category): void {
    router.visit(CategoryController.archive(category.id), {
        preserveScroll: true,
    });
}

function unarchive(category: Category): void {
    router.visit(CategoryController.unarchive(category.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Categories" />

    <h1 class="sr-only">Categories</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Categories"
                description="Group your spending. Archived categories stay on old expenses."
            />
            <Button
                size="sm"
                class="shrink-0 px-2.5"
                data-test="add-category"
                @click="openAdd"
            >
                <Plus /> Add
            </Button>
        </div>

        <ul class="divide-y rounded-2xl border bg-card">
            <li
                v-for="category in active"
                :key="category.id"
                class="flex items-center gap-3 px-4 py-2"
            >
                <CategoryBadge :category="category" />
                <span class="min-w-0 flex-1 truncate text-sm">
                    {{ category.name }}
                </span>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="-mr-1 shrink-0 rounded-full p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                            :aria-label="`More for ${category.name}`"
                            :data-test="`category-actions-${category.id}`"
                        >
                            <Ellipsis class="size-4" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem @select="openEdit(category)">
                            <Pencil /> Edit
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-if="active.length > 1"
                            @select="archive(category)"
                        >
                            <Archive /> Archive
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </li>
        </ul>

        <section v-if="archived.length" class="space-y-2">
            <h2 class="text-sm font-semibold">
                Archived
                <span class="font-normal text-muted-foreground">
                    · {{ archived.length }}
                </span>
            </h2>
            <ul class="divide-y rounded-2xl border bg-card">
                <li
                    v-for="category in archived"
                    :key="category.id"
                    class="flex items-center gap-3 px-4 py-2"
                >
                    <CategoryBadge :category="category" class="opacity-60" />
                    <span
                        class="min-w-0 flex-1 truncate text-sm text-muted-foreground"
                    >
                        {{ category.name }}
                    </span>
                    <Button
                        size="sm"
                        variant="outline"
                        :data-test="`unarchive-${category.id}`"
                        @click="unarchive(category)"
                    >
                        Unarchive
                    </Button>
                </li>
            </ul>
        </section>

        <CategoryDialog v-model:open="dialogOpen" :category="editing" />
    </div>
</template>
