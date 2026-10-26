/** PROTOTYPE helpers — formatting for fixture data. Not production code. */

const cad = new Intl.NumberFormat('en-CA', {
    style: 'currency',
    currency: 'CAD',
});
const cadWhole = new Intl.NumberFormat('en-CA', {
    style: 'currency',
    currency: 'CAD',
    maximumFractionDigits: 0,
});
const shortDate = new Intl.DateTimeFormat('en-CA', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
});

export const money = (cents: number): string => cad.format(cents / 100);

export const moneyWhole = (cents: number): string =>
    cadWhole.format(Math.round(cents / 100));

export const compactMoney = (cents: number): string => {
    const dollars = cents / 100;

    if (dollars < 1000) {
        return `$${Math.round(dollars)}`;
    }

    const thousands = dollars / 1000;

    return Number.isInteger(thousands)
        ? `$${thousands}K`
        : `$${thousands.toFixed(1)}K`;
};

export const daysFromNow = (days: number): Date => {
    const date = new Date();
    date.setDate(date.getDate() + days);

    return date;
};

export function relativeDue(days: number): string {
    if (days < -1) {
        return `${-days} days overdue`;
    }

    if (days === -1) {
        return '1 day overdue';
    }

    if (days === 0) {
        return 'Today';
    }

    if (days === 1) {
        return 'Tomorrow';
    }

    if (days < 7) {
        return `In ${days} days`;
    }

    return shortDate.format(daysFromNow(days));
}

export const monthName = (
    offset = 0,
    style: 'long' | 'short' = 'long',
): string => {
    const date = new Date();
    date.setDate(1);
    date.setMonth(date.getMonth() + offset);

    return new Intl.DateTimeFormat('en-CA', { month: style }).format(date);
};

export const daysLeftInMonth = (): number => {
    const today = new Date();

    return (
        new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate() -
        today.getDate()
    );
};

/**
 * Mirrors the planned Money::fromInput(): parse the typed string with integer
 * arithmetic, never `parseFloat(x) * 100` (which turns 19.99 into 1998.9999…).
 */
export function parseCents(input: string): number | null {
    const cleaned = input.trim().replace(/^\$/, '').replace(/,/g, '');

    if (!/^\d{1,7}(\.\d{1,2})?$/.test(cleaned)) {
        return null;
    }

    const [whole, fraction = ''] = cleaned.split('.');

    return Number(whole) * 100 + Number(fraction.padEnd(2, '0'));
}
