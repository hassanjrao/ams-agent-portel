import type { PassengerDraft, PassengerType } from '@/types/portal';

export const TITLES: Record<PassengerType, string[]> = {
    adult: ['MR', 'MRS', 'MS'],
    child: ['MSTR', 'MISS'],
    infant: ['MSTR', 'MISS'],
};

export function blankPassenger(type: PassengerType): PassengerDraft {
    return {
        type,
        title: TITLES[type][0],
        given_name: '',
        surname: '',
        passport_number: '',
        nationality: '',
        date_of_birth: '',
        passport_expiry: '',
        ticket_number: '',
    };
}

/**
 * Resize the passenger list to the counts chosen, keeping names already typed: adults first,
 * then children, then infants, as airlines list them.
 */
export function syncPassengers(
    current: PassengerDraft[],
    counts: Record<PassengerType, number>,
): PassengerDraft[] {
    const types: PassengerType[] = ['adult', 'child', 'infant'];

    return types.flatMap((type) => {
        const existing = current.filter((p) => p.type === type);

        return Array.from(
            { length: Math.max(0, counts[type] || 0) },
            (_, i) => existing[i] ?? blankPassenger(type),
        );
    });
}

// "Adult 2", "Child 1": the label of a passenger within its own type.
export function passengerLabel(list: PassengerDraft[], index: number): string {
    const type = list[index].type;
    const n = list.slice(0, index + 1).filter((p) => p.type === type).length;

    return `${type.charAt(0).toUpperCase()}${type.slice(1)} ${n}`;
}

// Passengers as the server takes them: blank optional fields as nulls.
export function passengersPayload(list: PassengerDraft[], withTickets = false) {
    return list.map((p) => ({
        type: p.type,
        title: p.title,
        given_name: p.given_name.trim(),
        surname: p.surname.trim(),
        passport_number: p.passport_number.trim() || null,
        nationality: p.nationality.trim() || null,
        date_of_birth: p.date_of_birth || null,
        passport_expiry: p.passport_expiry || null,
        ...(withTickets
            ? { ticket_number: p.ticket_number.trim() || null }
            : {}),
    }));
}
