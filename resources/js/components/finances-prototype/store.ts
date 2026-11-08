/**
 * PROTOTYPE store for the Finances tab — in-memory fake data shaped like the
 * planned schema (categories, payment methods, commitments shown as "bills",
 * planned project payments, expenses, skipped occurrences). Nothing touches the
 * backend; a reload resets it. Dates are relative to today.
 *
 * Planned project payments are modelled here as one-off bills carrying a
 * `project`, which is enough to see where they'd surface in Finances. The real
 * model keeps them on the project (#6).
 */
import {
    Car,
    CircleEllipsis,
    Hammer,
    House,
    PawPrint,
    ShoppingCart,
    Tv,
    UtensilsCrossed,
    Zap,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, reactive, ref } from 'vue';
import { toast } from 'vue-sonner';

/** Each category carries an icon and a colour tone (badge background + ink). */
export type Category = {
    id: string;
    name: string;
    icon: Component;
    tone: string;
};
export type Contact = { id: string; name: string; role: string };
export type PaymentMethod = { id: string; name: string };

/** 0 = once, otherwise repeats every N months. */
export type Repeat = 0 | 1 | 3 | 12;

export type Bill = {
    id: string;
    name: string;
    categoryId: string;
    amountCents: number;
    variable: boolean;
    firstDueOn: string;
    everyMonths: Repeat;
    autopay: boolean;
    paymentMethodId: string | null;
    contactId?: string;
    project?: { name: string; contractor: string };
};

export type Expense = {
    id: string;
    paidOn: string;
    amountCents: number;
    categoryId: string;
    note: string;
    paymentMethodId: string | null;
    paidToId?: string | null;
    billId: string | null;
    occurrenceOn: string | null;
};

export type OccurrenceStatus = 'paid' | 'due' | 'overdue' | 'skipped';

export type Occurrence = {
    key: string;
    bill: Bill;
    dueOn: string;
    amountCents: number;
    status: OccurrenceStatus;
    expense?: Expense;
};

export const categories: Category[] = [
    {
        id: 'housing',
        name: 'Housing',
        icon: House,
        tone: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    },
    {
        id: 'groceries',
        name: 'Groceries',
        icon: ShoppingCart,
        tone: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    },
    {
        id: 'dining',
        name: 'Dining',
        icon: UtensilsCrossed,
        tone: 'bg-pink-100 text-pink-700 dark:bg-pink-950 dark:text-pink-300',
    },
    {
        id: 'transport',
        name: 'Transport',
        icon: Car,
        tone: 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
    },
    {
        id: 'utilities',
        name: 'Utilities',
        icon: Zap,
        tone: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-300',
    },
    {
        id: 'subscriptions',
        name: 'Subscriptions',
        icon: Tv,
        tone: 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
    },
    {
        id: 'pets',
        name: 'Pets',
        icon: PawPrint,
        tone: 'bg-teal-100 text-teal-700 dark:bg-teal-950 dark:text-teal-300',
    },
    {
        id: 'home',
        name: 'Home & projects',
        icon: Hammer,
        tone: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    },
    {
        id: 'other',
        name: 'Other',
        icon: CircleEllipsis,
        tone: 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
    },
];

export const contactRoles = [
    'Insurance broker',
    'Mortgage broker',
    'Realtor',
    'Contractor',
    'Plumber',
    'Electrician',
    'Roofer',
    'Handyman',
    'Vet',
    'Utility provider',
    'Other',
];

export const contacts = reactive<Contact[]>([
    { id: 'sam', name: 'Sam Patel', role: 'Mortgage broker' },
    { id: 'jane', name: 'Jane Ko', role: 'Insurance broker' },
    { id: 'northline', name: 'Northline Exteriors', role: 'Roofer' },
    { id: 'hydro-one', name: 'Hydro One', role: 'Utility provider' },
]);

export const paymentMethods: PaymentMethod[] = [
    { id: 'td-chq', name: 'TD chequing' },
    { id: 'ws-cash', name: 'WS cash' },
    { id: 'td-visa', name: 'TD Visa' },
    { id: 'ws-visa', name: 'WS Visa Infinite+' },
];

export const repeatLabels: Record<Repeat, string> = {
    0: 'Once',
    1: 'Monthly',
    3: 'Every 3 months',
    12: 'Yearly',
};

const pad = (n: number): string => String(n).padStart(2, '0');

export const iso = (date: Date): string =>
    `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

export const today = iso(new Date());

export const parseIso = (value: string): Date => {
    const [y, m, d] = value.split('-').map(Number);

    return new Date(y, m - 1, d);
};

export const daysBetween = (from: string, to: string): number =>
    Math.round(
        (parseIso(to).getTime() - parseIso(from).getTime()) / 86_400_000,
    );

/** First-of-month for a month offset from the current month. */
export const monthStart = (offset: number): Date => {
    const date = new Date();
    date.setDate(1);
    date.setMonth(date.getMonth() + offset);

    return date;
};

/** A date in the month `offset` months from now, as YYYY-MM-DD. */
const dayIn = (offset: number, day: number): string => {
    const start = monthStart(offset);

    return iso(new Date(start.getFullYear(), start.getMonth(), day));
};

const monthly = (day: number): string => `2020-01-${pad(day)}`;

export const bills = reactive<Bill[]>([
    {
        id: 'mortgage',
        name: 'Mortgage',
        categoryId: 'housing',
        amountCents: 214000,
        variable: false,
        firstDueOn: monthly(1),
        everyMonths: 1,
        autopay: true,
        paymentMethodId: 'td-chq',
        contactId: 'sam',
    },
    {
        id: 'internet',
        name: 'Internet',
        categoryId: 'utilities',
        amountCents: 8999,
        variable: false,
        firstDueOn: monthly(3),
        everyMonths: 1,
        autopay: true,
        paymentMethodId: 'td-visa',
    },
    {
        id: 'mobile',
        name: 'Mobile phones',
        categoryId: 'utilities',
        amountCents: 9605,
        variable: false,
        firstDueOn: monthly(5),
        everyMonths: 1,
        autopay: false,
        paymentMethodId: 'td-visa',
    },
    {
        id: 'netflix',
        name: 'Netflix',
        categoryId: 'subscriptions',
        amountCents: 2099,
        variable: false,
        firstDueOn: monthly(12),
        everyMonths: 1,
        autopay: true,
        paymentMethodId: 'ws-visa',
    },
    {
        id: 'car',
        name: 'Car insurance',
        categoryId: 'transport',
        amountCents: 18750,
        variable: false,
        firstDueOn: monthly(15),
        everyMonths: 1,
        autopay: true,
        paymentMethodId: 'td-chq',
        contactId: 'jane',
    },
    {
        id: 'gas',
        name: 'Gas',
        categoryId: 'utilities',
        amountCents: 6400,
        variable: true,
        firstDueOn: monthly(18),
        everyMonths: 1,
        autopay: false,
        paymentMethodId: 'td-visa',
    },
    {
        id: 'hydro',
        name: 'Hydro',
        categoryId: 'utilities',
        amountCents: 11800,
        variable: true,
        firstDueOn: monthly(22),
        everyMonths: 1,
        autopay: false,
        paymentMethodId: 'td-visa',
        contactId: 'hydro-one',
    },
    {
        id: 'spotify',
        name: 'Spotify',
        categoryId: 'subscriptions',
        amountCents: 1299,
        variable: false,
        firstDueOn: monthly(26),
        everyMonths: 1,
        autopay: true,
        paymentMethodId: 'ws-visa',
    },
    {
        id: 'water',
        name: 'Water',
        categoryId: 'utilities',
        amountCents: 14200,
        variable: true,
        firstDueOn: monthly(28),
        everyMonths: 3,
        autopay: false,
        paymentMethodId: 'td-chq',
    },
    ...(['Deposit · 25%', 'Halfway · 25%', 'Final · 50%'] as const).map(
        (label, index): Bill => ({
            id: `eaves-${index}`,
            name: `Eaves & soffits — ${label}`,
            categoryId: 'home',
            amountCents: [240000, 240000, 480000][index],
            variable: false,
            firstDueOn: dayIn(index - 1, [10, 4, 15][index]),
            everyMonths: 0,
            autopay: false,
            paymentMethodId: 'td-chq',
            contactId: 'northline',
            project: {
                name: 'Eaves, soffits & fascia',
                contractor: 'Northline Exteriors',
            },
        }),
    ),
]);

/** The one bill left unpaid this month, so "overdue" shows up. */
const leftUnpaidThisMonth = 'mobile';

const monthIndex = (date: Date): number =>
    date.getFullYear() * 12 + date.getMonth();

/** The bill's due date in the month starting at `start`, or null if it isn't due that month. */
const dueDateIn = (bill: Bill, start: Date): string | null => {
    const first = parseIso(bill.firstDueOn);
    const monthsSince = monthIndex(start) - monthIndex(first);

    if (
        monthsSince < 0 ||
        (bill.everyMonths === 0
            ? monthsSince !== 0
            : monthsSince % bill.everyMonths !== 0)
    ) {
        return null;
    }

    const lastDay = new Date(
        start.getFullYear(),
        start.getMonth() + 1,
        0,
    ).getDate();

    return iso(
        new Date(
            start.getFullYear(),
            start.getMonth(),
            Math.min(first.getDate(), lastDay),
        ),
    );
};

/** Small deterministic "random" so the fixtures look varied but stable. */
const wobble = (seed: number, spread: number): number =>
    Math.round((((seed * 9301 + 49297) % 233280) / 233280 - 0.5) * spread);

function seedExpenses(): Expense[] {
    const rows: Expense[] = [];
    let id = 0;
    const add = (row: Omit<Expense, 'id'>) =>
        rows.push({ id: `seed-${++id}`, ...row });

    for (let offset = -3; offset <= 0; offset++) {
        const start = monthStart(offset);
        const monthSeed = start.getMonth() + 1;

        for (const bill of bills) {
            const dueOn = dueDateIn(bill, start);

            if (
                !dueOn ||
                dueOn >= today ||
                (offset === 0 && bill.id === leftUnpaidThisMonth)
            ) {
                continue;
            }

            add({
                paidOn: bill.autopay
                    ? dueOn
                    : iso(new Date(parseIso(dueOn).getTime() - 86_400_000)),
                amountCents: bill.variable
                    ? bill.amountCents +
                      wobble(monthSeed * 7 + bill.amountCents, 3000)
                    : bill.amountCents,
                categoryId: bill.categoryId,
                note: bill.name,
                paymentMethodId: bill.paymentMethodId,
                billId: bill.id,
                occurrenceOn: dueOn,
            });
        }

        const everyday: [number, string, string, number, string][] = [
            [2, 'groceries', 'Loblaws', 14200, 'td-visa'],
            [4, 'transport', 'Shell', 6800, 'ws-visa'],
            [6, 'dining', 'Pizzeria night', 5600, 'ws-visa'],
            [7, 'groceries', 'Costco', 21800, 'td-visa'],
            [10, 'pets', 'Pet Valu — kibble', 8900, 'ws-visa'],
            [11, 'groceries', 'No Frills', 9600, 'td-visa'],
            [14, 'home', 'Home Depot — furnace filters', 4700, 'td-visa'],
            [16, 'groceries', 'Loblaws', 12300, 'td-visa'],
            [17, 'transport', 'Shell', 7100, 'ws-visa'],
            [20, 'dining', 'Brunch', 6400, 'ws-visa'],
            [23, 'groceries', 'Farmers market', 5400, 'ws-cash'],
            [25, 'other', 'Birthday gift', 8000, 'ws-visa'],
            [27, 'groceries', 'Costco', 19700, 'td-visa'],
        ];

        for (const [
            day,
            categoryId,
            note,
            cents,
            paymentMethodId,
        ] of everyday) {
            const paidOn = iso(
                new Date(start.getFullYear(), start.getMonth(), day),
            );

            if (paidOn > today) {
                continue;
            }

            add({
                paidOn,
                amountCents: cents + wobble(monthSeed * 31 + day, 2400),
                categoryId,
                note,
                paymentMethodId,
                billId: null,
                occurrenceOn: null,
            });
        }
    }

    return rows;
}

export const state = reactive({
    expenses: seedExpenses(),
    skipped: new Set<string>(),
    /** Deleted expenses and undone payments, newest first, restorable. */
    deleted: [] as {
        expense: Expense;
        reason: 'deleted' | 'unpaid';
        at: Date;
    }[],
    monthOffset: 0,
    log: [] as string[],
});

let nextId = 0;
const record = (message: string) =>
    state.log.unshift(`${new Date().toLocaleTimeString()} — ${message}`);

export const category = (id: string): Category =>
    categories.find((c) => c.id === id) ?? categories[categories.length - 1];

export const contactLabel = (id?: string | null): string | null => {
    const contact = contacts.find((c) => c.id === id);

    return contact ? `${contact.name} · ${contact.role.toLowerCase()}` : null;
};

export function addContact(input: Omit<Contact, 'id'>): string {
    const id = `contact-${++nextId}`;
    contacts.push({ id, ...input });
    record(`Added contact "${input.name}" (${input.role})`);

    return id;
}

export const categoryName = (id: string): string =>
    categories.find((c) => c.id === id)?.name ?? id;
export const paymentMethodName = (id: string | null): string =>
    id ? (paymentMethods.find((m) => m.id === id)?.name ?? id) : '—';
export const billFor = (expense: Expense): Bill | undefined =>
    expense.billId
        ? bills.find((bill) => bill.id === expense.billId)
        : undefined;

export const selectedMonth = computed(() => monthStart(state.monthOffset));
export const monthLabel = computed(() =>
    new Intl.DateTimeFormat('en-CA', { month: 'long', year: 'numeric' }).format(
        selectedMonth.value,
    ),
);
export const isCurrentMonth = computed(() => state.monthOffset === 0);

const inSelectedMonth = (value: string): boolean => {
    const start = selectedMonth.value;

    return value.startsWith(
        `${start.getFullYear()}-${pad(start.getMonth() + 1)}`,
    );
};

/** Expenses paid in the selected month, newest first (cash basis). */
export const monthExpenses = computed(() =>
    state.expenses
        .filter((expense) => inSelectedMonth(expense.paidOn))
        .sort(
            (a, b) =>
                b.paidOn.localeCompare(a.paidOn) || b.id.localeCompare(a.id),
        ),
);

/** What the Paid form pre-fills: last time's amount, or the expected amount. */
export const lastPaidCents = (bill: Bill): number =>
    state.expenses
        .filter((expense) => expense.billId === bill.id)
        .sort((a, b) => b.paidOn.localeCompare(a.paidOn))[0]?.amountCents ??
    bill.amountCents;

/** Bill occurrences (and planned project payments) due in the selected month, by due date. */
export const monthOccurrences = computed<Occurrence[]>(() =>
    bills
        .flatMap((bill) => {
            const dueOn = dueDateIn(bill, selectedMonth.value);

            if (!dueOn) {
                return [];
            }

            const key = `${bill.id}:${dueOn}`;
            const expense = state.expenses.find(
                (e) => e.billId === bill.id && e.occurrenceOn === dueOn,
            );
            const status: OccurrenceStatus = expense
                ? 'paid'
                : state.skipped.has(key)
                  ? 'skipped'
                  : dueOn < today
                    ? 'overdue'
                    : 'due';

            return [
                {
                    key,
                    bill,
                    dueOn,
                    status,
                    expense,
                    amountCents:
                        expense?.amountCents ??
                        (bill.variable
                            ? lastPaidCents(bill)
                            : bill.amountCents),
                },
            ];
        })
        .sort((a, b) => a.dueOn.localeCompare(b.dueOn)),
);

const sum = (values: number[]): number =>
    values.reduce((total, value) => total + value, 0);

export const unpaid = computed(() =>
    monthOccurrences.value.filter(
        (o) => o.status === 'due' || o.status === 'overdue',
    ),
);

export const totals = computed(() => {
    const spent = sum(monthExpenses.value.map((e) => e.amountCents));
    const stillDue = sum(unpaid.value.map((o) => o.amountCents));

    return { spent, stillDue, projected: spent + stillDue };
});

export const byCategory = computed(() =>
    categories
        .map((category) => ({
            ...category,
            spent: sum(
                monthExpenses.value
                    .filter((e) => e.categoryId === category.id)
                    .map((e) => e.amountCents),
            ),
            due: sum(
                unpaid.value
                    .filter((o) => o.bill.categoryId === category.id)
                    .map((o) => o.amountCents),
            ),
        }))
        .filter((row) => row.spent + row.due > 0)
        .sort((a, b) => b.spent + b.due - (a.spent + a.due)),
);

export function shiftMonth(step: number): void {
    state.monthOffset = Math.max(-3, Math.min(2, state.monthOffset + step));
}

const label = (expense: Expense): string =>
    expense.note || categoryName(expense.categoryId);

const dollars = (cents: number): string => (cents / 100).toFixed(2);

/**
 * Every change confirms with a toast that can undo it. Anything removed also
 * lands in "Recently deleted", so a missed toast isn't the last chance.
 */
function confirmWithUndo(message: string, undo: () => void): void {
    toast(message, { action: { label: 'Undo', onClick: undo } });
}

const removeExpense = (expense: Expense): void => {
    state.expenses = state.expenses.filter((e) => e.id !== expense.id);
};

export function addExpense(
    input: Omit<Expense, 'id' | 'billId' | 'occurrenceOn'>,
): void {
    const expense: Expense = {
        id: `new-${++nextId}`,
        billId: null,
        occurrenceOn: null,
        ...input,
    };
    state.expenses.push(expense);
    record(
        `Added expense "${label(expense)}" ${dollars(expense.amountCents)} on ${expense.paidOn}`,
    );
    confirmWithUndo(`Added "${label(expense)}"`, () => {
        removeExpense(expense);
        record(`Undid adding "${label(expense)}"`);
    });
}

export function updateExpense(
    expense: Expense,
    changes: Partial<Omit<Expense, 'id'>>,
): void {
    const before = { ...expense };
    const target = state.expenses.find((e) => e.id === expense.id);

    if (!target) {
        return;
    }

    Object.assign(target, changes);
    record(`Edited "${label(target)}"`);
    confirmWithUndo(`Saved "${label(target)}"`, () => {
        Object.assign(target, before);
        record(`Undid edit to "${label(target)}"`);
    });
}

export function addBill(input: Omit<Bill, 'id'>): void {
    const bill: Bill = { id: `bill-${++nextId}`, ...input };
    bills.push(bill);
    record(
        `Added bill "${bill.name}" ${dollars(bill.amountCents)}, ${repeatLabels[bill.everyMonths].toLowerCase()} from ${bill.firstDueOn}`,
    );
    confirmWithUndo(`Added bill "${bill.name}"`, () => {
        bills.splice(bills.indexOf(bill), 1);
        record(`Undid adding bill "${bill.name}"`);
    });
}

export function payOccurrence(
    occurrence: Occurrence,
    input: {
        amountCents: number;
        paidOn: string;
        paymentMethodId: string | null;
    },
): void {
    const expense: Expense = {
        id: `new-${++nextId}`,
        categoryId: occurrence.bill.categoryId,
        note: occurrence.bill.name,
        billId: occurrence.bill.id,
        occurrenceOn: occurrence.dueOn,
        ...input,
    };
    state.expenses.push(expense);
    record(
        `Paid ${occurrence.bill.name} (due ${occurrence.dueOn}) ${dollars(input.amountCents)} on ${input.paidOn}`,
    );
    confirmWithUndo(`Paid ${occurrence.bill.name}`, () => {
        removeExpense(expense);
        record(`Undid paying ${occurrence.bill.name}`);
    });
}

/** Marks a paid occurrence unpaid again; the payment goes to Recently deleted. */
export function undoPayment(occurrence: Occurrence): void {
    const expense = occurrence.expense;

    if (!expense) {
        return;
    }

    removeExpense(expense);
    state.deleted.unshift({ expense, reason: 'unpaid', at: new Date() });
    record(`Marked ${occurrence.bill.name} (due ${occurrence.dueOn}) unpaid`);
    confirmWithUndo(`${occurrence.bill.name} marked unpaid`, () =>
        restoreExpense(expense),
    );
}

export function toggleSkip(occurrence: Occurrence): void {
    const skipping = !state.skipped.has(occurrence.key);
    const apply = (skip: boolean) =>
        skip
            ? state.skipped.add(occurrence.key)
            : state.skipped.delete(occurrence.key);

    apply(skipping);
    record(
        `${skipping ? 'Skipped' : 'Un-skipped'} ${occurrence.bill.name} (${occurrence.dueOn})`,
    );
    confirmWithUndo(
        `${skipping ? 'Skipped' : 'Un-skipped'} ${occurrence.bill.name}`,
        () => {
            apply(!skipping);
            record(
                `Undid ${skipping ? 'skip' : 'un-skip'} of ${occurrence.bill.name}`,
            );
        },
    );
}

export function deleteExpense(expense: Expense): void {
    removeExpense(expense);
    state.deleted.unshift({ expense, reason: 'deleted', at: new Date() });
    record(`Deleted expense "${label(expense)}"`);
    confirmWithUndo(`Deleted "${label(expense)}"`, () =>
        restoreExpense(expense),
    );
}

export function restoreExpense(expense: Expense): void {
    state.deleted = state.deleted.filter(
        (entry) => entry.expense.id !== expense.id,
    );
    state.expenses.push(expense);
    record(`Restored "${label(expense)}"`);
}

/** Dialog plumbing shared by every variant. */
export const paying = ref<Occurrence | null>(null);
export const addingExpense = ref(false);
export const addMode = ref<'expense' | 'bill'>('expense');

export const editing = ref<Expense | null>(null);
export const confirmingDelete = ref<Expense | null>(null);

export function openAdd(mode: 'expense' | 'bill' = 'expense'): void {
    editing.value = null;
    addMode.value = mode;
    addingExpense.value = true;
}

export function openEdit(expense: Expense): void {
    editing.value = expense;
    addMode.value = 'expense';
    addingExpense.value = true;
}
