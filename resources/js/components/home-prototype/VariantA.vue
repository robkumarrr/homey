<script setup lang="ts">
/**
 * PROTOTYPE variant A — "Agenda": time first. One chronological list of
 * everything that needs doing or paying; checking things off is the primary
 * action. Money and projects are context in a side rail.
 */
import { usePage } from '@inertiajs/vue3';
import { Check, TriangleAlert } from '@lucide/vue';
import { computed, ref } from 'vue';
import type { AgendaItem, DoneItem } from './fixtures';
import { agenda, budget, doneThisWeek, renovation } from './fixtures';
import {
    daysLeftInMonth,
    money,
    moneyWhole,
    monthName,
    relativeDue,
} from './format';
import Meter from './Meter.vue';

const page = usePage();
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);

const hour = new Date().getHours();
const greeting =
    hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';

const items = ref<AgendaItem[]>(agenda.map((item) => ({ ...item })));
const done = ref<DoneItem[]>(doneThisWeek.map((item) => ({ ...item })));

function complete(item: AgendaItem): void {
    items.value = items.value.filter((candidate) => candidate.id !== item.id);
    done.value = [
        { id: item.id, title: item.title, when: 'Just now', icon: item.icon },
        ...done.value,
    ];
}

const groups = computed(() =>
    [
        {
            key: 'attention',
            title: 'Needs attention',
            items: items.value.filter((item) => item.dueInDays < 0),
        },
        {
            key: 'week',
            title: 'This week',
            items: items.value.filter(
                (item) => item.dueInDays >= 0 && item.dueInDays < 7,
            ),
        },
        {
            key: 'next',
            title: 'Next week',
            items: items.value.filter(
                (item) => item.dueInDays >= 7 && item.dueInDays < 14,
            ),
        },
        {
            key: 'later',
            title: 'Later',
            items: items.value.filter((item) => item.dueInDays >= 14),
        },
    ].filter((group) => group.items.length > 0),
);

const needsYouCount = computed(
    () => items.value.filter((item) => item.dueInDays < 7).length,
);
const dueSoonCents = computed(() =>
    items.value
        .filter((item) => item.dueInDays < 14)
        .reduce((total, item) => total + (item.amountCents ?? 0), 0),
);

const spentCents = budget.reduce(
    (total, category) => total + category.spentCents,
    0,
);
const budgetCents = budget.reduce(
    (total, category) => total + category.budgetCents,
    0,
);
const paidCents = renovation.payments
    .filter((payment) => payment.paid)
    .reduce((total, payment) => total + payment.cents, 0);
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-8">
        <div class="min-w-0 space-y-6">
            <header class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight md:text-3xl">
                    {{ greeting }}, {{ firstName }}
                </h1>
                <p class="text-muted-foreground">
                    {{ needsYouCount }} things this week ·
                    {{ moneyWhole(dueSoonCents) }} due in the next two weeks
                </p>
            </header>

            <div class="rounded-2xl border border-border bg-card p-4 lg:hidden">
                <div class="mb-2 flex items-baseline justify-between text-sm">
                    <span class="font-medium">{{ monthName() }}</span>
                    <span class="text-muted-foreground"
                        >{{ moneyWhole(spentCents) }} of
                        {{ moneyWhole(budgetCents) }}</span
                    >
                </div>
                <Meter
                    :value-cents="spentCents"
                    :limit-cents="budgetCents"
                    show-status
                />
            </div>

            <section v-for="group in groups" :key="group.key" class="space-y-2">
                <h2
                    class="px-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    {{ group.title }}
                </h2>
                <ul
                    class="divide-y divide-border overflow-hidden rounded-2xl border border-border bg-card"
                >
                    <li
                        v-for="item in group.items"
                        :key="item.id"
                        class="relative flex items-center gap-3 px-4 py-3.5"
                    >
                        <span
                            v-if="item.dueInDays < 0"
                            class="absolute inset-y-0 left-0 w-1 bg-(--meter-over)"
                            aria-hidden="true"
                        />
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-secondary text-secondary-foreground"
                        >
                            <component :is="item.icon" class="size-4.5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ item.title }}</p>
                            <p
                                class="flex items-center gap-1 truncate text-sm text-muted-foreground"
                            >
                                <TriangleAlert
                                    v-if="item.dueInDays < 0"
                                    class="size-3.5 shrink-0 text-(--meter-over)"
                                />
                                <span
                                    :class="
                                        item.dueInDays < 0
                                            ? 'font-medium text-foreground'
                                            : ''
                                    "
                                    >{{ relativeDue(item.dueInDays) }}</span
                                >
                                <span class="hidden sm:inline"
                                    >· {{ item.detail }}</span
                                >
                            </p>
                        </div>
                        <div
                            class="flex shrink-0 flex-col items-end gap-1.5 sm:flex-row sm:items-center sm:gap-3"
                        >
                            <span
                                v-if="item.amountCents"
                                class="text-sm font-medium tabular-nums"
                                >{{ money(item.amountCents) }}</span
                            >
                            <button
                                type="button"
                                class="rounded-full border border-border px-3 py-1 text-xs font-medium hover:bg-accent"
                                @click="
                                    item.action === 'Details'
                                        ? undefined
                                        : complete(item)
                                "
                            >
                                {{ item.action }}
                            </button>
                        </div>
                    </li>
                </ul>
            </section>
        </div>

        <aside class="space-y-4">
            <section
                class="hidden rounded-2xl border border-border bg-card p-5 lg:block"
            >
                <h2 class="text-sm font-medium text-muted-foreground">
                    {{ monthName() }} spending
                </h2>
                <p class="mt-1 text-2xl font-semibold">
                    {{ moneyWhole(spentCents) }}
                </p>
                <p class="mb-3 text-sm text-muted-foreground">
                    of {{ moneyWhole(budgetCents) }} ·
                    {{ daysLeftInMonth() }} days left
                </p>
                <Meter
                    :value-cents="spentCents"
                    :limit-cents="budgetCents"
                    show-status
                />
            </section>

            <section class="rounded-2xl border border-border bg-card p-5">
                <h2 class="text-sm font-medium text-muted-foreground">
                    Project
                </h2>
                <p class="mt-1 font-semibold">{{ renovation.name }}</p>
                <p class="mb-3 text-sm text-muted-foreground">
                    {{ moneyWhole(paidCents) }} of
                    {{ moneyWhole(renovation.contractCents) }} paid
                </p>
                <Meter
                    :value-cents="paidCents"
                    :limit-cents="renovation.contractCents"
                    progress
                    show-status
                />
            </section>

            <section class="rounded-2xl border border-border bg-card p-5">
                <h2 class="mb-3 text-sm font-medium text-muted-foreground">
                    Done this week
                </h2>
                <ul class="space-y-2.5">
                    <li
                        v-for="item in done"
                        :key="item.id"
                        class="flex items-center gap-2.5 text-sm"
                    >
                        <span
                            class="flex size-6 items-center justify-center rounded-full bg-(--meter-ok-track) text-(--meter-ok)"
                        >
                            <Check class="size-3.5" />
                        </span>
                        <span class="flex-1 truncate">{{ item.title }}</span>
                        <span class="text-muted-foreground">{{
                            item.when
                        }}</span>
                    </li>
                </ul>
            </section>
        </aside>
    </div>
</template>
