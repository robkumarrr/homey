export type CategoryIcon =
    | 'house'
    | 'shopping-cart'
    | 'utensils-crossed'
    | 'car'
    | 'zap'
    | 'tv'
    | 'paw-print'
    | 'hammer'
    | 'circle-ellipsis';

export type CategoryColour =
    | 'blue'
    | 'emerald'
    | 'pink'
    | 'sky'
    | 'yellow'
    | 'violet'
    | 'teal'
    | 'amber'
    | 'zinc';

export type Category = {
    id: number;
    name: string;
    icon: CategoryIcon;
    colour: CategoryColour;
    archived: boolean;
};

export type Expense = {
    id: number;
    name: string;
    category_id: number;
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

export type SortKey = 'date' | 'amount' | 'category' | 'name';
export type SortDirection = 'asc' | 'desc';
