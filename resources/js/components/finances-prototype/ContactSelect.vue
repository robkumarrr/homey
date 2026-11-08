<script setup lang="ts">
/**
 * PROTOTYPE contact picker: the family's contacts, plus "New contact…" at the
 * bottom, which opens a second dialog and selects the new contact on save.
 */
import { ref } from 'vue';
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
import { addContact, contactRoles, contacts } from './store';

const NEW = '__new__';

const model = defineModel<string>({ required: true });
defineProps<{ id: string; selectClass: string; emptyLabel: string }>();

const creating = ref(false);
const draft = ref({ name: '', role: 'Contractor', phone: '' });
const nameError = ref('');
let previous = '';

function onChange(event: Event): void {
    const value = (event.target as HTMLSelectElement).value;

    if (value === NEW) {
        previous = model.value;
        draft.value = { name: '', role: 'Contractor', phone: '' };
        nameError.value = '';
        creating.value = true;

        return;
    }

    model.value = value;
}

function cancel(): void {
    model.value = previous;
    creating.value = false;
}

function save(): void {
    if (draft.value.name.trim() === '') {
        nameError.value = 'Give the contact a name.';

        return;
    }

    model.value = addContact({
        name: draft.value.name.trim(),
        role: draft.value.role,
    });
    creating.value = false;
}
</script>

<template>
    <div>
        <select
            :id="id"
            :value="creating ? NEW : model"
            :class="selectClass"
            @change="onChange"
        >
            <option value="">{{ emptyLabel }}</option>
            <option
                v-for="contact in contacts"
                :key="contact.id"
                :value="contact.id"
            >
                {{ contact.name }} · {{ contact.role }}
            </option>
            <option :value="NEW">+ New contact…</option>
        </select>

        <Dialog :open="creating" @update:open="(open) => !open && cancel()">
            <DialogContent
                class="max-h-[calc(100dvh-2rem)] overflow-y-auto p-5 sm:max-w-sm sm:p-6"
            >
                <DialogHeader>
                    <DialogTitle>New contact</DialogTitle>
                    <DialogDescription>
                        Saved to your Contacts, and picked here.
                    </DialogDescription>
                </DialogHeader>
                <form class="grid gap-4" @submit.prevent="save">
                    <div class="grid gap-2">
                        <Label for="contact-name">Name</Label>
                        <Input
                            id="contact-name"
                            v-model="draft.name"
                            placeholder="Northline Exteriors"
                            :aria-invalid="nameError !== ''"
                        />
                        <p v-if="nameError" class="text-sm text-destructive">
                            {{ nameError }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="contact-role">Role</Label>
                        <select
                            id="contact-role"
                            v-model="draft.role"
                            :class="selectClass"
                        >
                            <option
                                v-for="role in contactRoles"
                                :key="role"
                                :value="role"
                            >
                                {{ role }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="contact-phone">Phone (optional)</Label>
                        <Input
                            id="contact-phone"
                            v-model="draft.phone"
                            type="tel"
                        />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="ghost" @click="cancel">
                            Cancel
                        </Button>
                        <Button type="submit">Save contact</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
