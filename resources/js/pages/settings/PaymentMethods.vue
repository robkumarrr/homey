<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Archive, Ellipsis, Pencil, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import PaymentMethodController from '@/actions/App/Http/Controllers/Settings/PaymentMethodController';
import PaymentMethodDialog from '@/components/finances/PaymentMethodDialog.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    describePaymentMethod,
    paymentMethodKinds,
} from '@/lib/paymentMethods';
import type { PaymentMethod } from '@/types';

const props = defineProps<{ paymentMethods: PaymentMethod[] }>();

const active = computed(() => props.paymentMethods.filter((m) => !m.archived));
const archived = computed(() => props.paymentMethods.filter((m) => m.archived));

const dialogOpen = ref(false);
const editing = ref<PaymentMethod | null>(null);

function openAdd(): void {
    editing.value = null;
    dialogOpen.value = true;
}

function openEdit(method: PaymentMethod): void {
    editing.value = method;
    dialogOpen.value = true;
}

function archive(method: PaymentMethod): void {
    router.visit(PaymentMethodController.archive(method.id), {
        preserveScroll: true,
    });
}

function unarchive(method: PaymentMethod): void {
    router.visit(PaymentMethodController.unarchive(method.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Payment methods" />

    <h1 class="sr-only">Payment methods</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Payment methods"
                description="The cards and accounts you pay from. Expenses can record which one paid."
            />
            <Button
                size="sm"
                class="shrink-0 px-2.5"
                data-test="add-payment-method"
                @click="openAdd"
            >
                <Plus /> Add
            </Button>
        </div>

        <p
            v-if="active.length === 0"
            class="rounded-2xl border border-dashed px-4 py-8 text-center text-sm text-muted-foreground"
        >
            No payment methods yet. Add a card or account, like "TD Visa".
        </p>
        <ul v-else class="divide-y rounded-2xl border bg-card">
            <li
                v-for="method in active"
                :key="method.id"
                class="flex items-center gap-3 px-4 py-2"
            >
                <span
                    class="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"
                >
                    <component
                        :is="paymentMethodKinds[method.kind].icon"
                        class="size-4"
                        aria-hidden="true"
                    />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm leading-5">{{ method.name }}</p>
                    <p
                        class="truncate text-[11px] leading-4 text-muted-foreground"
                    >
                        {{ describePaymentMethod(method) }}
                    </p>
                </div>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="-mr-1 shrink-0 rounded-full p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                            :aria-label="`More for ${method.name}`"
                            :data-test="`payment-method-actions-${method.id}`"
                        >
                            <Ellipsis class="size-4" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem @select="openEdit(method)">
                            <Pencil /> Edit
                        </DropdownMenuItem>
                        <DropdownMenuItem @select="archive(method)">
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
                    v-for="method in archived"
                    :key="method.id"
                    class="flex items-center gap-3 px-4 py-2"
                >
                    <div class="min-w-0 flex-1 text-muted-foreground">
                        <p class="truncate text-sm leading-5">
                            {{ method.name }}
                        </p>
                        <p class="truncate text-[11px] leading-4">
                            {{ describePaymentMethod(method) }}
                        </p>
                    </div>
                    <Button
                        size="sm"
                        variant="outline"
                        :data-test="`unarchive-${method.id}`"
                        @click="unarchive(method)"
                    >
                        Unarchive
                    </Button>
                </li>
            </ul>
        </section>

        <PaymentMethodDialog
            v-model:open="dialogOpen"
            :payment-method="editing"
        />
    </div>
</template>
