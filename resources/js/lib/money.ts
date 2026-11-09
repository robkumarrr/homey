const cad = new Intl.NumberFormat('en-CA', {
    style: 'currency',
    currency: 'CAD',
});

const dayFormat = new Intl.DateTimeFormat('en-CA', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
});

/** "$1,234.56" for integer cents. */
export const formatCents = (cents: number): string => cad.format(cents / 100);

/** "19.99" for an amount input, from integer cents. */
export const centsToInput = (cents: number): string => (cents / 100).toFixed(2);

/** "Fri, Nov 7" for a YYYY-MM-DD date, read as a calendar date (no timezone shift). */
export function formatDay(isoDate: string): string {
    const [year, month, day] = isoDate.split('-').map(Number);

    return dayFormat.format(new Date(year, month - 1, day));
}

/** Today in the viewer's own timezone, as YYYY-MM-DD. */
export function todayIso(): string {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

/**
 * The date a new expense or bill starts on while viewing `month` (YYYY-MM):
 * today in the current month, otherwise the same day of that month, clamped
 * to its last day.
 */
export function defaultDateIn(month: string): string {
    const today = todayIso();

    if (today.startsWith(month)) {
        return today;
    }

    const [year, monthNumber] = month.split('-').map(Number);
    const lastDay = new Date(year, monthNumber, 0).getDate();
    const day = Math.min(Number(today.slice(8)), lastDay);

    return `${month}-${String(day).padStart(2, '0')}`;
}

/** "Fri, Nov 7" for a moment in time (ISO 8601), in the viewer's own timezone. */
export const formatInstant = (iso: string): string =>
    dayFormat.format(new Date(iso));
