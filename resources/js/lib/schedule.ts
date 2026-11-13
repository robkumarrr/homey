import { formatDay } from '@/lib/money';
import type { Commitment, RepeatFrequency } from '@/types';

export type RepeatOption = {
    key: string;
    label: string;
    frequency: RepeatFrequency;
    interval: number;
};

/** The schedules the Bill form offers, in order. */
export const repeatOptions: RepeatOption[] = [
    { key: 'once', label: 'Once', frequency: 'once', interval: 1 },
    { key: 'weekly', label: 'Weekly', frequency: 'weekly', interval: 1 },
    {
        key: 'biweekly',
        label: 'Every 2 weeks',
        frequency: 'weekly',
        interval: 2,
    },
    { key: 'monthly', label: 'Monthly', frequency: 'monthly', interval: 1 },
    {
        key: 'quarterly',
        label: 'Every 3 months',
        frequency: 'monthly',
        interval: 3,
    },
    {
        key: 'semiannual',
        label: 'Every 6 months',
        frequency: 'monthly',
        interval: 6,
    },
    { key: 'yearly', label: 'Yearly', frequency: 'yearly', interval: 1 },
];

export const repeatOptionFor = (
    frequency: RepeatFrequency,
    interval: number,
): RepeatOption =>
    repeatOptions.find(
        (option) =>
            option.frequency === frequency && option.interval === interval,
    ) ?? repeatOptions[3];

const parts = (isoDate: string): Date => {
    const [year, month, day] = isoDate.split('-').map(Number);

    return new Date(year, month - 1, day);
};

const weekday = new Intl.DateTimeFormat('en-CA', { weekday: 'long' });
const monthDay = new Intl.DateTimeFormat('en-CA', {
    month: 'short',
    day: 'numeric',
});

const units: Record<Exclude<RepeatFrequency, 'once'>, [string, string]> = {
    weekly: ['Weekly', 'weeks'],
    monthly: ['Monthly', 'months'],
    yearly: ['Yearly', 'years'],
};

function ordinal(day: number): string {
    const suffix =
        day % 10 === 1 && day !== 11
            ? 'st'
            : day % 10 === 2 && day !== 12
              ? 'nd'
              : day % 10 === 3 && day !== 13
                ? 'rd'
                : 'th';

    return `${day}${suffix}`;
}

/** "Monthly on the 15th", "Every 2 weeks on Friday", "Once, Fri, Nov 20". */
export function describeSchedule(bill: Commitment): string {
    if (bill.frequency === 'once') {
        return `Once, ${formatDay(bill.starts_on)}`;
    }

    const start = parts(bill.starts_on);
    const [single, plural] = units[bill.frequency];
    const label =
        bill.interval === 1 ? single : `Every ${bill.interval} ${plural}`;

    switch (bill.frequency) {
        case 'weekly':
            return `${label} on ${weekday.format(start)}`;
        case 'monthly':
            return `${label} on the ${ordinal(start.getDate())}`;
        case 'yearly':
            return `${label} on ${monthDay.format(start)}`;
    }
}
