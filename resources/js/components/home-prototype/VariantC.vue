<script setup lang="ts">
/**
 * PROTOTYPE variant C — "Areas": place first. A bento hub of the home's areas,
 * each tile a live status line; drilling into an area is the primary action.
 * The house switcher hints at the multi-house future (one house in v1).
 */
import {
    ArrowRight,
    Check,
    ChevronDown,
    FileText,
    Hammer,
    House,
    PawPrint,
    Receipt,
    ShieldCheck,
    TriangleAlert,
    Wallet,
    Wrench,
    Zap,
} from '@lucide/vue';
import {
    agenda,
    budget,
    doneThisWeek,
    houseName,
    incomeThisMonthCents,
    pets,
    renovation,
    utilities,
    utilitiesLastYearMonthlyCents,
    utilitiesMonthlyCents,
    percentChange,
} from './fixtures';
import { money, moneyWhole, monthName, relativeDue } from './format';
import Meter from './Meter.vue';

const paidCents = renovation.payments
    .filter((payment) => payment.paid)
    .reduce((total, payment) => total + payment.cents, 0);

const bills = agenda.filter((item) =>
    ['bill', 'subscription', 'utility'].includes(item.kind),
);
const billsThisMonth = bills.filter((item) => item.dueInDays < 30);
const nextBill = bills[0];

const careTasks = agenda.filter((item) => item.area === 'care');
const overdueTask = careTasks.find((item) => item.dueInDays < 0);

const spentCents = budget.reduce(
    (total, category) => total + category.spentCents,
    0,
);
const budgetCents = budget.reduce(
    (total, category) => total + category.budgetCents,
    0,
);
const overCategories = budget.filter(
    (category) => category.spentCents > category.budgetCents,
).length;

const tile =
    'group flex flex-col rounded-3xl border border-border bg-card p-5 text-left transition hover:-translate-y-0.5 hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';
</script>

<template>
    <div class="space-y-5">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <button
                    type="button"
                    class="flex items-center gap-1.5 text-2xl font-semibold tracking-tight md:text-3xl"
                >
                    {{ houseName }}
                    <ChevronDown class="size-5 text-muted-foreground" />
                </button>
                <p class="text-muted-foreground">
                    Everything about this home, by area
                </p>
            </div>
            <span
                class="rounded-full bg-secondary px-3 py-1 text-xs text-secondary-foreground"
                >1 house · 1 family</span
            >
        </header>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
            <!-- Projects: the big tile -->
            <button type="button" :class="[tile, 'col-span-2 md:row-span-2']">
                <div class="mb-4 flex items-center justify-between">
                    <span
                        class="flex items-center gap-2 text-sm font-medium text-muted-foreground"
                        ><Hammer class="size-4" /> Projects</span
                    >
                    <ArrowRight
                        class="size-4 text-muted-foreground transition group-hover:translate-x-0.5"
                    />
                </div>
                <p class="text-xl font-semibold">{{ renovation.name }}</p>
                <p class="mb-4 text-sm text-muted-foreground">
                    {{ renovation.contractor }}
                </p>
                <p class="mb-2 text-sm">
                    <span class="text-2xl font-semibold">{{
                        moneyWhole(paidCents)
                    }}</span>
                    <span class="text-muted-foreground">
                        of {{ moneyWhole(renovation.contractCents) }} paid</span
                    >
                </p>
                <Meter
                    :value-cents="paidCents"
                    :limit-cents="renovation.contractCents"
                    progress
                />
                <ul class="mt-4 space-y-2 text-sm">
                    <li
                        v-for="payment in renovation.payments"
                        :key="payment.label"
                        class="flex items-center gap-2"
                    >
                        <span
                            class="flex size-5 items-center justify-center rounded-full"
                            :class="
                                payment.paid
                                    ? 'bg-(--meter-ok-track) text-(--meter-ok)'
                                    : 'border border-border'
                            "
                        >
                            <Check v-if="payment.paid" class="size-3" />
                        </span>
                        <span class="flex-1">{{ payment.label }}</span>
                        <span
                            class="tabular-nums"
                            :class="
                                payment.paid
                                    ? 'text-muted-foreground'
                                    : 'font-medium'
                            "
                            >{{ money(payment.cents) }}</span
                        >
                    </li>
                </ul>
                <div class="mt-auto flex flex-wrap gap-1.5 pt-4">
                    <span
                        v-for="document in renovation.documents"
                        :key="document.label"
                        class="flex items-center gap-1 rounded-full border border-border px-2 py-0.5 text-xs"
                        :class="
                            document.state === 'superseded'
                                ? 'text-muted-foreground line-through'
                                : ''
                        "
                    >
                        <FileText class="size-3" /> {{ document.label }}
                    </span>
                    <span
                        class="flex items-center gap-1 rounded-full border border-dashed border-border px-2 py-0.5 text-xs text-muted-foreground"
                    >
                        <ShieldCheck class="size-3" /> Warranty ·
                        {{ renovation.warranty.toLowerCase() }}
                    </span>
                </div>
            </button>

            <!-- Maintenance -->
            <button type="button" :class="[tile, 'col-span-2']">
                <div class="mb-3 flex items-center justify-between">
                    <span
                        class="flex items-center gap-2 text-sm font-medium text-muted-foreground"
                        ><Wrench class="size-4" /> Maintenance</span
                    >
                    <ArrowRight
                        class="size-4 text-muted-foreground transition group-hover:translate-x-0.5"
                    />
                </div>
                <p
                    v-if="overdueTask"
                    class="flex items-center gap-2 font-semibold"
                >
                    <TriangleAlert class="size-4 text-(--meter-over)" />
                    {{ overdueTask.title }}
                </p>
                <p
                    v-if="overdueTask"
                    class="mb-3 text-sm text-muted-foreground"
                >
                    {{ relativeDue(overdueTask.dueInDays) }}
                </p>
                <ul class="space-y-1.5 text-sm">
                    <li
                        v-for="task in careTasks.filter(
                            (t) => t.dueInDays >= 0,
                        )"
                        :key="task.id"
                        class="flex justify-between gap-3"
                    >
                        <span class="truncate">{{ task.title }}</span>
                        <span class="shrink-0 text-muted-foreground">{{
                            relativeDue(task.dueInDays)
                        }}</span>
                    </li>
                    <li
                        v-for="task in doneThisWeek.filter(
                            (t) => t.id === 'gutters',
                        )"
                        :key="task.id"
                        class="flex justify-between gap-3 text-muted-foreground"
                    >
                        <span class="flex items-center gap-1.5 truncate"
                            ><Check class="size-3.5 text-(--meter-ok)" />
                            {{ task.title }}</span
                        >
                        <span class="shrink-0">{{ task.when }}</span>
                    </li>
                </ul>
            </button>

            <!-- Bills -->
            <button type="button" :class="tile">
                <span
                    class="mb-3 flex items-center gap-2 text-sm font-medium text-muted-foreground"
                    ><Receipt class="size-4" /> Bills</span
                >
                <p class="text-2xl font-semibold">
                    {{ billsThisMonth.length }}
                </p>
                <p class="text-sm text-muted-foreground">due in 30 days</p>
                <p class="mt-auto pt-3 text-sm">
                    {{ nextBill.title }}
                    <span class="text-muted-foreground"
                        >· {{ relativeDue(nextBill.dueInDays) }}</span
                    >
                </p>
            </button>

            <!-- Utilities -->
            <button type="button" :class="tile">
                <span
                    class="mb-3 flex items-center gap-2 text-sm font-medium text-muted-foreground"
                    ><Zap class="size-4" /> Utilities</span
                >
                <p class="text-2xl font-semibold">
                    {{ moneyWhole(utilitiesMonthlyCents) }}
                </p>
                <p class="text-sm text-muted-foreground">
                    this month ·
                    {{
                        percentChange(
                            utilitiesMonthlyCents,
                            utilitiesLastYearMonthlyCents,
                        )
                    }}% vs last year
                </p>
                <p class="mt-auto pt-3 text-sm">
                    Hydro {{ money(utilities.hydro.lastBillCents) }}
                    <span class="text-muted-foreground"
                        >· Gas {{ money(utilities.gas.lastBillCents) }}</span
                    >
                </p>
            </button>

            <!-- Budget -->
            <button type="button" :class="[tile, 'col-span-2 md:col-span-2']">
                <div class="mb-3 flex items-center justify-between">
                    <span
                        class="flex items-center gap-2 text-sm font-medium text-muted-foreground"
                        ><Wallet class="size-4" />
                        {{ monthName() }} budget</span
                    >
                    <ArrowRight
                        class="size-4 text-muted-foreground transition group-hover:translate-x-0.5"
                    />
                </div>
                <p class="mb-2">
                    <span class="text-2xl font-semibold">{{
                        moneyWhole(spentCents)
                    }}</span>
                    <span class="ml-1.5 text-sm text-muted-foreground"
                        >of {{ moneyWhole(budgetCents) }}</span
                    >
                </p>
                <Meter
                    :value-cents="spentCents"
                    :limit-cents="budgetCents"
                    show-status
                />
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ overCategories }} categories over · income
                    {{ moneyWhole(incomeThisMonthCents) }}
                </p>
            </button>

            <!-- Pets -->
            <button
                v-for="pet in pets"
                :key="pet.name"
                type="button"
                :class="tile"
            >
                <span
                    class="mb-3 flex items-center gap-2 text-sm font-medium text-muted-foreground"
                    ><PawPrint class="size-4" /> Pets</span
                >
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-10 items-center justify-center rounded-full bg-secondary font-semibold text-secondary-foreground"
                        >{{ pet.name[0] }}</span
                    >
                    <div>
                        <p class="font-semibold">{{ pet.name }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ pet.species }}
                        </p>
                    </div>
                </div>
                <p class="mt-auto pt-3 text-sm">
                    {{ pet.next }}
                    <span class="text-muted-foreground"
                        >· {{ relativeDue(pet.nextInDays) }}</span
                    >
                </p>
            </button>

            <!-- The house itself -->
            <button type="button" :class="tile">
                <span
                    class="mb-3 flex items-center gap-2 text-sm font-medium text-muted-foreground"
                    ><House class="size-4" /> House</span
                >
                <p class="font-semibold">{{ houseName }}</p>
                <p class="text-sm text-muted-foreground">
                    Details, documents, warranties
                </p>
                <p class="mt-auto pt-3 text-sm text-muted-foreground">
                    + Add another house
                </p>
            </button>
        </div>
    </div>
</template>
