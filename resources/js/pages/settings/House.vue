<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import HouseController from '@/actions/App/Http/Controllers/Settings/HouseController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    house: {
        id: number;
        name: string;
        address: string | null;
    };
}>();
</script>

<template>
    <Head title="House settings" />

    <h1 class="sr-only">House settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="House"
            description="Update your house's name and address"
        />

        <Form
            v-bind="HouseController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="house.name"
                    required
                    placeholder="Home"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="address">Address</Label>
                <Input
                    id="address"
                    class="mt-1 block w-full"
                    name="address"
                    :default-value="house.address ?? ''"
                    autocomplete="street-address"
                    placeholder="123 Maple Street, Toronto"
                />
                <InputError class="mt-2" :message="errors.address" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-house-button"
                    >Save</Button
                >
            </div>
        </Form>
    </div>
</template>
