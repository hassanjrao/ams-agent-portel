import { formatDate } from '@/lib/format';
import type { Leg } from '@/types/portal';

// What a group's "Copy" button puts on the clipboard, for agents to paste into WhatsApp:
//
//   SAUDI ARABIAN AIRLINES
//   21 Days
//   SV 801 15 Jan 2027 MUX-JED 16:35 19:55 46-KG Baggage
//   SV 800 04 Feb 2027 JED-MUX 08:35 14:55 46-KG Baggage
//   PKR 183000
export type CopyableGroup = {
    airline: { name: string };
    duration_days: number | null;
    adult_fare: number;
    legs: Leg[];
};

// "SV801" -> "SV 801": the airline code, then the number.
function flightNumber(value: string): string {
    const match = value
        .replace(/\s+/g, '')
        .match(/^([A-Z0-9]{2})(\d{1,4}[A-Z]?)$/i);

    return match ? `${match[1].toUpperCase()} ${match[2]}` : value;
}

// "46", "46 kg" or "23+7" -> "46-KG Baggage" / "23+7-KG Baggage".
function baggage(value: string | null): string | null {
    const amount = value?.trim().replace(/\s*kgs?\.?$/i, '');

    return amount ? `${amount}-KG Baggage` : null;
}

export function groupText(group: CopyableGroup): string {
    const lines = [group.airline.name.toUpperCase()];
    if (group.duration_days) {
        lines.push(`${group.duration_days} Days`);
    }
    for (const leg of group.legs) {
        lines.push(
            [
                flightNumber(leg.flight_number),
                formatDate(leg.departs_on),
                `${leg.origin}-${leg.destination}`,
                leg.departs_time,
                leg.arrives_time,
                baggage(leg.baggage),
            ]
                .filter(Boolean)
                .join(' '),
        );
    }
    lines.push(`PKR ${group.adult_fare}`);

    return lines.join('\n');
}

// The clipboard API, with the old textarea route for browsers or pages where it isn't allowed.
export async function copyText(text: string): Promise<boolean> {
    try {
        await navigator.clipboard.writeText(text);

        return true;
    } catch {
        const area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        const copied = document.execCommand('copy');
        area.remove();

        return copied;
    }
}
