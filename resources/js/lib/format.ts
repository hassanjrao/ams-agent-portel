// "PKR 1,690,000". Amounts are whole rupees everywhere in the portal.
export function formatPKR(amount: number): string {
    return `PKR ${amount.toLocaleString('en-US')}`;
}

// "15 Nov 2026" from "2026-11-15". The T00:00 keeps it in local time instead of UTC midnight.
export function formatDate(isoDate: string): string {
    return new Date(`${isoDate.slice(0, 10)}T00:00:00`).toLocaleDateString(
        'en-GB',
        { day: '2-digit', month: 'short', year: 'numeric' },
    );
}

// "Saturday" style weekday, useful next to departure dates.
export function formatWeekday(isoDate: string): string {
    return new Date(`${isoDate.slice(0, 10)}T00:00:00`).toLocaleDateString(
        'en-GB',
        { weekday: 'short' },
    );
}

export function titleCase(value: string): string {
    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}
