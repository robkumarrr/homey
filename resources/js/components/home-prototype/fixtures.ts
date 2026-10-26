/**
 * PROTOTYPE fixtures — in-memory stub data shaped like the planned schema
 * (commitments, expenses, budget allocations, maintenance tasks, renovation).
 * Nothing here touches the backend. Dates are relative to today so the
 * prototype stays plausible whenever it is opened.
 */
import {
    BellRing,
    Car,
    Droplet,
    Flame,
    Hammer,
    House,
    PawPrint,
    Smartphone,
    Tv,
    Wifi,
    Wrench,
    Zap,
} from '@lucide/vue';
import type { Component } from 'vue';

export type Area = 'money' | 'care' | 'projects' | 'pets';
export type AgendaKind =
    'bill' | 'subscription' | 'utility' | 'task' | 'appointment' | 'payment';

export type AgendaItem = {
    id: string;
    kind: AgendaKind;
    area: Area;
    icon: Component;
    title: string;
    detail: string;
    dueInDays: number;
    amountCents?: number;
    action: string;
};

export type DoneItem = {
    id: string;
    title: string;
    when: string;
    icon: Component;
};

export type BudgetCategory = {
    name: string;
    spentCents: number;
    budgetCents: number;
};

export type Expense = {
    id: string;
    description: string;
    category: string;
    amountCents: number;
    daysAgo: number;
};

export const houseName = 'Maple Avenue';

export const agenda: AgendaItem[] = [
    {
        id: 'furnace',
        kind: 'task',
        area: 'care',
        icon: Wrench,
        title: 'Replace furnace filter',
        detail: 'Every 3 months · last done 99 days ago',
        dueInDays: -9,
        action: 'Mark done',
    },
    {
        id: 'hydro',
        kind: 'utility',
        area: 'money',
        icon: Zap,
        title: 'Hydro',
        detail: 'Hydro One',
        dueInDays: 2,
        amountCents: 11842,
        action: 'Mark paid',
    },
    {
        id: 'mobile',
        kind: 'bill',
        area: 'money',
        icon: Smartphone,
        title: 'Mobile phones',
        detail: 'Rogers · 2 lines',
        dueInDays: 4,
        amountCents: 9605,
        action: 'Mark paid',
    },
    {
        id: 'netflix',
        kind: 'subscription',
        area: 'money',
        icon: Tv,
        title: 'Netflix',
        detail: 'Subscription · monthly',
        dueInDays: 6,
        amountCents: 2099,
        action: 'Mark paid',
    },
    {
        id: 'mortgage',
        kind: 'bill',
        area: 'money',
        icon: House,
        title: 'Mortgage',
        detail: 'Monthly payment',
        dueInDays: 8,
        amountCents: 214000,
        action: 'Mark paid',
    },
    {
        id: 'vet',
        kind: 'appointment',
        area: 'pets',
        icon: PawPrint,
        title: 'Rosie — annual checkup',
        detail: 'Dr. Patel · 10:30 am',
        dueInDays: 9,
        action: 'Details',
    },
    {
        id: 'eaves',
        kind: 'payment',
        area: 'projects',
        icon: Hammer,
        title: 'Eaves & soffits — final payment',
        detail: 'Due on completion · 50% of contract',
        dueInDays: 12,
        amountCents: 480000,
        action: 'Details',
    },
    {
        id: 'gas',
        kind: 'utility',
        area: 'money',
        icon: Flame,
        title: 'Gas',
        detail: 'Enbridge',
        dueInDays: 15,
        amountCents: 6430,
        action: 'Mark paid',
    },
    {
        id: 'car',
        kind: 'bill',
        area: 'money',
        icon: Car,
        title: 'Car insurance',
        detail: 'Monthly',
        dueInDays: 18,
        amountCents: 18750,
        action: 'Mark paid',
    },
    {
        id: 'alarms',
        kind: 'task',
        area: 'care',
        icon: BellRing,
        title: 'Test smoke & CO alarms',
        detail: 'Every 6 months',
        dueInDays: 20,
        action: 'Mark done',
    },
    {
        id: 'water',
        kind: 'utility',
        area: 'money',
        icon: Droplet,
        title: 'Water',
        detail: 'Toronto Water · quarterly',
        dueInDays: 26,
        amountCents: 14210,
        action: 'Mark paid',
    },
];

export const doneThisWeek: DoneItem[] = [
    { id: 'gutters', title: 'Clean gutters', when: '2 days ago', icon: Wrench },
    { id: 'internet', title: 'Internet paid', when: '6 days ago', icon: Wifi },
];

export const budget: BudgetCategory[] = [
    { name: 'Housing', spentCents: 232750, budgetCents: 245000 },
    { name: 'Groceries', spentCents: 74218, budgetCents: 90000 },
    { name: 'Transport', spentCents: 46830, budgetCents: 42000 },
    { name: 'Utilities', spentCents: 30127, budgetCents: 38000 },
    { name: 'Pets', spentCents: 21100, budgetCents: 15000 },
    { name: 'Dining & fun', spentCents: 18749, budgetCents: 30000 },
    { name: 'Subscriptions', spentCents: 8796, budgetCents: 12000 },
];

export const incomeThisMonthCents = 845000;

export const recentExpenses: Expense[] = [
    {
        id: 'e1',
        description: 'Loblaws',
        category: 'Groceries',
        amountCents: 14237,
        daysAgo: 1,
    },
    {
        id: 'e2',
        description: 'Shell',
        category: 'Transport',
        amountCents: 6820,
        daysAgo: 2,
    },
    {
        id: 'e3',
        description: 'Pizzeria night',
        category: 'Dining & fun',
        amountCents: 5640,
        daysAgo: 3,
    },
    {
        id: 'e4',
        description: 'Dr. Patel Veterinary',
        category: 'Pets',
        amountCents: 21100,
        daysAgo: 5,
    },
    {
        id: 'e5',
        description: 'Bell internet',
        category: 'Utilities',
        amountCents: 8999,
        daysAgo: 6,
    },
];

/** Last six complete months of spending, oldest first. */
export const monthlySpendCents = [
    451200, 498000, 462000, 514000, 475500, 489000,
];

export const utilities = {
    hydro: { lastBillCents: 11842, lastYearCents: 12610 },
    gas: { lastBillCents: 6430, lastYearCents: 7120 },
    water: { lastBillCents: 14210, lastYearCents: 13880 },
};

export const renovation = {
    name: 'Eaves, soffits & fascia',
    contractor: 'Northline Exteriors',
    contractCents: 960000,
    payments: [
        { label: 'Deposit · 25%', cents: 240000, paid: true },
        { label: 'Halfway · 25%', cents: 240000, paid: true },
        { label: 'Final · 50%', cents: 480000, paid: false },
    ],
    documents: [
        { label: 'Quote', state: 'superseded' as const },
        { label: 'Invoice (revised)', state: 'current' as const },
        { label: 'Deposit receipt', state: 'current' as const },
        { label: 'Halfway receipt', state: 'current' as const },
    ],
    warranty: 'Issued at completion',
};

export const pets = [
    {
        name: 'Rosie',
        species: 'Dog',
        next: 'Annual checkup',
        nextInDays: 9,
        monthSpendCents: 21100,
    },
];

/** Cost-only utilities (usage is out of scope unless a provider API supplies it). */
export const utilitiesMonthlyCents =
    utilities.hydro.lastBillCents +
    utilities.gas.lastBillCents +
    Math.round(utilities.water.lastBillCents / 3);

export const utilitiesLastYearMonthlyCents =
    utilities.hydro.lastYearCents +
    utilities.gas.lastYearCents +
    Math.round(utilities.water.lastYearCents / 3);

export const percentChange = (now: number, before: number): number =>
    Math.round(((now - before) / before) * 100);
