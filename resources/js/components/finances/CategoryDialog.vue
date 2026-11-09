<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import CategoryController from '@/actions/App/Http/Controllers/Settings/CategoryController';
import CategoryBadge from '@/components/finances/CategoryBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    categoryIcons,
    categorySwatches,
    categoryTones,
} from '@/lib/categories';
import type { Category, CategoryColour, CategoryIcon } from '@/types';

/** Add a new category, or edit `category` when one is given. */
const props = defineProps<{ category: Category | null }>();

const open = defineModel<boolean>('open', { required: true });

const name = ref('');
const icon = ref<CategoryIcon>('circle-ellipsis');
const colour = ref<CategoryColour>('zinc');

/** Remount the form on every open so its defaults are fresh. */
const formKey = ref(0);
watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    formKey.value++;
    name.value = props.category?.name ?? '';
    icon.value = props.category?.icon ?? 'circle-ellipsis';
    colour.value = props.category?.colour ?? 'blue';
});

const icons = Object.keys(categoryIcons) as CategoryIcon[];
const colours = Object.keys(categorySwatches) as CategoryColour[];
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="max-h-[calc(100dvh-2rem)] overflow-y-auto p-5 sm:max-w-md sm:p-6"
        >
            <DialogHeader>
                <DialogTitle>
                    {{ category ? 'Edit category' : 'Add category' }}
                </DialogTitle>
                <DialogDescription>
                    Group your spending under a name, icon and colour.
                </DialogDescription>
            </DialogHeader>

            <Form
                :key="formKey"
                v-bind="
                    category
                        ? CategoryController.update.form(category.id)
                        : CategoryController.store.form()
                "
                :options="{ preserveScroll: true }"
                class="grid gap-4"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <div class="flex items-center gap-3 rounded-xl border p-3">
                    <CategoryBadge :category="{ name, icon, colour }" />
                    <span class="truncate text-sm">
                        {{ name || 'New category' }}
                    </span>
                </div>

                <div class="grid gap-2">
                    <Label for="category-name">Name</Label>
                    <Input
                        id="category-name"
                        v-model="name"
                        name="name"
                        placeholder="Kids"
                        autocomplete="off"
                        required
                        :aria-invalid="!!errors.name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <fieldset class="grid gap-2">
                    <legend class="mb-2 text-sm font-medium">Icon</legend>
                    <div class="grid grid-cols-7 gap-1.5 sm:grid-cols-9">
                        <label
                            v-for="option in icons"
                            :key="option"
                            class="cursor-pointer"
                            :data-test="`icon-${option}`"
                        >
                            <input
                                v-model="icon"
                                type="radio"
                                name="icon"
                                :value="option"
                                :aria-label="option"
                                class="peer sr-only"
                            />
                            <span
                                class="flex aspect-square items-center justify-center rounded-lg text-muted-foreground peer-focus-visible:ring-[3px] peer-focus-visible:ring-ring/50 hover:bg-accent"
                                :class="
                                    icon === option &&
                                    `${categoryTones[colour]} ring-2 ring-current`
                                "
                            >
                                <component
                                    :is="categoryIcons[option]"
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </span>
                        </label>
                    </div>
                    <InputError :message="errors.icon" />
                </fieldset>

                <fieldset class="grid gap-2">
                    <legend class="mb-2 text-sm font-medium">Colour</legend>
                    <div class="grid grid-cols-8 gap-2">
                        <label
                            v-for="option in colours"
                            :key="option"
                            class="cursor-pointer"
                            :data-test="`colour-${option}`"
                        >
                            <input
                                v-model="colour"
                                type="radio"
                                name="colour"
                                :value="option"
                                :aria-label="option"
                                class="peer sr-only"
                            />
                            <span
                                class="block aspect-square rounded-full ring-offset-2 ring-offset-background peer-checked:ring-2 peer-checked:ring-foreground peer-focus-visible:ring-[3px] peer-focus-visible:ring-ring/50"
                                :class="categorySwatches[option]"
                            />
                        </label>
                    </div>
                    <InputError :message="errors.colour" />
                </fieldset>

                <DialogFooter>
                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="save-category"
                    >
                        {{ category ? 'Save changes' : 'Add category' }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
