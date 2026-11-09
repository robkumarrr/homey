export type CategoryIcon =
    | 'house'
    | 'shopping-cart'
    | 'utensils-crossed'
    | 'car'
    | 'zap'
    | 'tv'
    | 'paw-print'
    | 'hammer'
    | 'circle-ellipsis'
    | 'shirt'
    | 'heart-pulse'
    | 'baby'
    | 'graduation-cap'
    | 'gift'
    | 'plane'
    | 'fuel'
    | 'dumbbell'
    | 'coffee'
    | 'wifi'
    | 'shield'
    | 'piggy-bank'
    | 'landmark'
    | 'wrench'
    | 'sprout'
    | 'sofa'
    | 'gamepad-2'
    | 'hand-heart';

export type CategoryColour =
    | 'blue'
    | 'emerald'
    | 'pink'
    | 'sky'
    | 'yellow'
    | 'violet'
    | 'teal'
    | 'amber'
    | 'zinc'
    | 'red'
    | 'orange'
    | 'lime'
    | 'cyan'
    | 'indigo'
    | 'fuchsia'
    | 'rose';

export type Category = {
    id: number;
    name: string;
    icon: CategoryIcon;
    colour: CategoryColour;
    archived: boolean;
};

export type PaymentMethodKind = 'credit_card' | 'bank_account';

export type PaymentMethod = {
    id: number;
    name: string;
    kind: PaymentMethodKind;
    last_four: string | null;
    archived: boolean;
};

export type Expense = {
    id: number;
    name: string;
    category_id: number;
    payment_method_id: number | null;
    amount_cents: number;
    paid_on: string;
};

export type DeletedExpense = {
    id: number;
    name: string;
    amount_cents: number;
    category_id: number;
    paid_on: string;
    deleted_at: string;
};

export type RepeatFrequency = 'once' | 'weekly' | 'monthly' | 'yearly';

/** A bill. "Commitment" is its name in code. */
export type Commitment = {
    id: number;
    name: string;
    category_id: number;
    payment_method_id: number | null;
    amount_varies: boolean;
    frequency: RepeatFrequency;
    interval: number;
    starts_on: string;
    ends_on: string | null;
    archived: boolean;
    /** The next due date from today, or null once the bill has ended. */
    next_due_on: string | null;
    /** What's owed on the next due date, or null while it needs entering. */
    next_amount_cents: number | null;
};

/** One due date of a bill. */
export type Occurrence = {
    commitment_id: number;
    due_on: string;
    /** What's owed on this date, or null while it needs entering. */
    amount_cents: number | null;
};

export type SortKey = 'date' | 'amount' | 'category' | 'name';
export type SortDirection = 'asc' | 'desc';
