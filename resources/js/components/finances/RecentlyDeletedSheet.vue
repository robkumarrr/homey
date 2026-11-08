<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import DeletedExpenseController from '@/actions/App/Http/Controllers/Finances/DeletedExpenseController';
import CategoryBadge from '@/components/finances/CategoryBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { formatCents, formatDay, formatInstant } from '@/lib/money';
import type { Category, DeletedExpense } from '@/types';

defineProps<{
    deletedExpenses: DeletedExpense[];
    categoryFor: (categoryId: number) => Category;
}>();

const open = defineModel<boolean>('open', { required: true });

function restore(deleted: DeletedExpense): void {
    router.visit(DeletedExpenseController.restore(deleted.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent class="w-full overflow-y-auto sm:max-w-md">
            <SheetHeader>
                <SheetTitle>Recently deleted</SheetTitle>
                <SheetDescription>
                    Deleted expenses stay here for 30 days, then they're gone
                    for good.
                </SheetDescription>
            </SheetHeader>
            <p
                v-if="deletedExpenses.length === 0"
                class="px-4 py-6 text-center text-sm text-muted-foreground"
            >
                Nothing deleted.
            </p>
            <ul class="divide-y px-4">
                <li
                    v-for="deleted in deletedExpenses"
                    :key="deleted.id"
                    class="flex items-center gap-3 py-2"
                >
                    <CategoryBadge
                        :category="categoryFor(deleted.category_id)"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm leading-5">
                            {{ deleted.name }}
                        </p>
                        <p
                            class="truncate text-[11px] leading-4 text-muted-foreground"
                        >
                            {{ formatCents(deleted.amount_cents) }} · paid
                            {{ formatDay(deleted.paid_on) }}
                        </p>
                        <p
                            class="truncate text-[11px] leading-4 text-muted-foreground"
                        >
                            Deleted {{ formatInstant(deleted.deleted_at) }}
                        </p>
                    </div>
                    <Button
                        size="sm"
                        variant="outline"
                        :data-test="`restore-${deleted.id}`"
                        @click="restore(deleted)"
                    >
                        Restore
                    </Button>
                </li>
            </ul>
        </SheetContent>
    </Sheet>
</template>
