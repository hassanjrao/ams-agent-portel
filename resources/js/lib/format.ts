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

// "1,690,000": an exact amount without the currency, for places that label it separately.
export function formatAmount(amount: number): string {
    return amount.toLocaleString('en-US');
}

// "15 Nov" from "2026-11-15", for compact lists.
export function formatShortDate(isoDate: string): string {
    return new Date(`${isoDate.slice(0, 10)}T00:00:00`).toLocaleDateString(
        'en-GB',
        { day: '2-digit', month: 'short' },
    );
}

// "17 Nov 2026, 14:05" in the viewer's own time, for timestamps (not flight times).
export function formatDateTime(iso: string): string {
    return new Date(iso).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    });
}

// "1:05:09" or "42:10": what is left of a hold.
export function formatCountdown(seconds: number): string {
    const s = Math.max(0, Math.floor(seconds));
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = String(s % 60).padStart(2, '0');

    return h > 0 ? `${h}:${String(m).padStart(2, '0')}:${sec}` : `${m}:${sec}`;
}

// An agency balance: positive is owed to AMS, negative is credit the agency holds.
export function balanceLabel(balance: number): string {
    if (balance === 0) {
        return 'Settled';
    }

    return balance > 0 ? 'Payable to AMS' : 'Credit with AMS';
}

// "3 adults, 1 child, 1 infant"
export function paxSummary(
    adults: number,
    children: number,
    infants: number,
): string {
    const part = (n: number, one: string, many: string) =>
        n > 0 ? `${n} ${n === 1 ? one : many}` : null;

    return [
        part(adults, 'adult', 'adults'),
        part(children, 'child', 'children'),
        part(infants, 'infant', 'infants'),
    ]
        .filter(Boolean)
        .join(', ');
}
