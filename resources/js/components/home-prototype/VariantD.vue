<script setup lang="ts">
/**
 * PROTOTYPE variant D — hybrid of A and C. The agenda leads (what needs you
 * this week, check-off as the primary action); the home's areas sit beside it
 * as tiles to drill into. Money is deliberately demoted to one small tile.
 */
import { usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    Hammer,
    PawPrint,
    Receipt,
    TriangleAlert,
    Wallet,
    Wrench,
    Zap,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import type { AgendaItem, DoneItem } from './fixtures';
import {
    agenda,
    budget,
    doneThisWeek,
    houseName,
    pets,
    renovation,
    utilitiesLastYearMonthlyCents,
    utilitiesMonthlyCents,
    percentChange,
} from './fixtures';
import { money, moneyWhole, monthName, relativeDue } from './format';
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

const overdue = computed(() =>
    items.value.filter((item) => item.dueInDays < 0),
);
const thisWeek = computed(() =>
    items.value.filter((item) => item.dueInDays >= 0 && item.dueInDays < 7),
);
const laterCount = computed(
    () => items.value.filter((item) => item.dueInDays >= 7).length,
);

const paidCents = renovation.payments
    .filter((payment) => payment.paid)
    .reduce((total, payment) => total + payment.cents, 0);
const nextPayment = renovation.payments.find((payment) => !payment.paid);

const maintenance = computed(() =>
    items.value.filter((item) => item.area === 'care'),
);
const bills = computed(() =>
    items.value.filter((item) =>
        ['bill', 'subscription', 'utility'].includes(item.kind),
    ),
);

const spentCents = budget.reduce((total, c) => total + c.spentCents, 0);
const budgetCents = budget.reduce((total, c) => total + c.budgetCents, 0);
const utilitiesChange = percentChange(
    utilitiesMonthlyCents,
    utilitiesLastYearMonthlyCents,
);

const tile =
    'group flex flex-col rounded-2xl border border-border bg-card p-4 text-left transition hover:-translate-y-0.5 hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_26rem] lg:gap-8">
        <div class="min-w-0 space-y-6">
            <header class="space-y-1">
                <p class="text-sm text-muted-foreground">{{ houseName }}</p>
                <h1 class="text-2xl font-semibold tracking-tight md:text-3xl">
                    {{ greeting }}, {{ firstName }}
                </h1>
                <p class="text-muted-foreground">
                    <template v-if="overdue.length"
                        >{{ overdue.length }} overdue ·
                    </template>
                    {{ thisWeek.length }} things this week
                </p>
            </header>

            <section v-if="overdue.length || thisWeek.length" class="space-y-2">
                <h2
                    class="px-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    This week
                </h2>
                <ul
                    class="divide-y divide-border overflow-hidden rounded-2xl border border-border bg-card"
                >
                    <li
                        v-for="item in [...overdue, ...thisWeek]"
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
                                <span
                                    v-if="item.amountCents"
                                    class="tabular-nums"
                                    >· {{ money(item.amountCents) }}</span
                                >
                            </p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 rounded-full border border-border px-3 py-1 text-xs font-medium hover:bg-accent"
                            @click="complete(item)"
                        >
                            {{
                                item.action === 'Details' ? 'Done' : item.action
                            }}
                        </button>
                    </li>
                </ul>
                <button
                    type="button"
                    class="flex items-center gap-1 px-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    {{ laterCount }} more coming up
                    <ArrowRight class="size-3.5" />
                </button>
            </section>

            <section v-if="done.length" class="space-y-2">
                <h2
                    class="px-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Done recently
                </h2>
                <ul class="flex flex-wrap gap-2">
                    <li
                        v-for="item in done"
                        :key="item.id"
                        class="flex items-center gap-1.5 rounded-full border border-border bg-card px-3 py-1 text-sm"
                    >
                        <Check class="size-3.5 text-(--meter-ok)" />
                        {{ item.title }}
                        <span class="text-muted-foreground"
                            >· {{ item.when }}</span
                        >
                    </li>
                </ul>
            </section>
        </div>

        <aside class="space-y-2">
            <h2
                class="px-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                Your home
            </h2>
            <div class="grid grid-cols-2 gap-3">
                <button type="button" :class="[tile, 'col-span-2']">
                    <span
                        class="mb-2 flex items-center justify-between text-sm text-muted-foreground"
                    >
                        <span class="flex items-center gap-2"
                            ><Hammer class="size-4" /> Projects</span
                        >
                        <ArrowRight
                            class="size-4 transition group-hover:translate-x-0.5"
                        />
                    </span>
                    <p class="font-semibold">{{ renovation.name }}</p>
                    <p class="mb-3 text-sm text-muted-foreground">
                        {{ moneyWhole(paidCents) }} of
                        {{ moneyWhole(renovation.contractCents) }} paid
                        <template v-if="nextPayment">
                            · next
                            {{ nextPayment.label.split(' ·')[0].toLowerCase() }}
                        </template>
                    </p>
                    <Meter
                        :value-cents="paidCents"
                        :limit-cents="renovation.contractCents"
                        progress
                    />
                </button>

                <button type="button" :class="[tile, 'col-span-2']">
                    <span
                        class="mb-2 flex items-center justify-between text-sm text-muted-foreground"
                    >
                        <span class="flex items-center gap-2"
                            ><Wrench class="size-4" /> Maintenance</span
                        >
                        <ArrowRight
                            class="size-4 transition group-hover:translate-x-0.5"
                        />
                    </span>
                    <ul class="space-y-1.5 text-sm">
                        <li
                            v-for="task in maintenance"
                            :key="task.id"
                            class="flex justify-between gap-3"
                        >
                            <span class="truncate">{{ task.title }}</span>
                            <span
                                class="shrink-0"
                                :class="
                                    task.dueInDays < 0
                                        ? 'font-medium text-(--meter-over)'
                                        : 'text-muted-foreground'
                                "
                                >{{ relativeDue(task.dueInDays) }}</span
                            >
                        </li>
                    </ul>
                </button>

                <button type="button" :class="tile">
                    <span
                        class="mb-2 flex items-center gap-2 text-sm text-muted-foreground"
                        ><Receipt class="size-4" /> Bills</span
                    >
                    <p class="text-xl font-semibold">{{ bills.length }}</p>
                    <p class="text-xs text-muted-foreground">coming up</p>
                </button>

                <button type="button" :class="tile">
                    <span
                        class="mb-2 flex items-center gap-2 text-sm text-muted-foreground"
                        ><Zap class="size-4" /> Utilities</span
                    >
                    <p class="text-xl font-semibold">
                        {{ moneyWhole(utilitiesMonthlyCents) }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        this month · {{ utilitiesChange }}% vs last year
                    </p>
                </button>

                <button
                    v-for="pet in pets"
                    :key="pet.name"
                    type="button"
                    :class="tile"
                >
                    <span
                        class="mb-2 flex items-center gap-2 text-sm text-muted-foreground"
                        ><PawPrint class="size-4" /> Pets</span
                    >
                    <p class="font-semibold">{{ pet.name }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ pet.next }} · {{ relativeDue(pet.nextInDays) }}
                    </p>
                </button>

                <button type="button" :class="tile">
                    <span
                        class="mb-2 flex items-center gap-2 text-sm text-muted-foreground"
                        ><Wallet class="size-4" /> {{ monthName() }}</span
                    >
                    <Meter
                        :value-cents="spentCents"
                        :limit-cents="budgetCents"
                        show-status
                    />
                </button>
            </div>
        </aside>
    </div>
</template>
