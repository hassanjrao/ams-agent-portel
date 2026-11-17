export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

// Laravel's LengthAwarePaginator as JSON.
export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

export type Option = {
    id: number;
    name: string;
};

export type AirlineOption = Option & { code: string };

export type Leg = {
    flight_number: string;
    origin: string;
    destination: string;
    departs_on: string;
    departs_time: string;
    arrives_on: string;
    arrives_time: string;
    baggage: string | null;
    meal: boolean;
};

// A group as agencies see it (GroupPresenter::card).
export type GroupCard = {
    id: number;
    sector: string;
    group_type: string;
    airline: { name: string; code: string };
    departure_date: string;
    return_date: string | null;
    duration_days: number | null;
    total_seats: number;
    seats_available: number;
    adult_fare: number;
    child_fare: number;
    infant_fare: number;
    hold_minutes: number;
    notes: string | null;
    bookable: boolean;
    legs: Leg[];
};

export type BookingStatus = 'on_hold' | 'confirmed' | 'cancelled' | 'expired';

// BookingPresenter::row
export type BookingRow = {
    id: number;
    reference: string;
    sector: string;
    departure_date: string;
    adults: number;
    children: number;
    infants: number;
    passengers: number;
    total_amount: number;
    status: BookingStatus;
    pnr: string | null;
    seconds_left: number | null;
    created_at: string;
    agency: { id: number; name: string; city: string } | null;
};

export type PassengerType = 'adult' | 'child' | 'infant';

export type Passenger = {
    id?: number;
    type: PassengerType;
    title: string;
    given_name: string;
    surname: string;
    passport_number: string | null;
    nationality: string | null;
    date_of_birth: string | null;
    passport_expiry: string | null;
    ticket_number?: string | null;
};

// A passenger being typed in a form: every field a string, blank when not given.
export type PassengerDraft = {
    type: PassengerType;
    title: string;
    given_name: string;
    surname: string;
    passport_number: string;
    nationality: string;
    date_of_birth: string;
    passport_expiry: string;
    ticket_number: string;
};

// PaymentPresenter::row
export type PaymentRow = {
    id: number;
    amount: number;
    paid_on: string;
    description: string | null;
    status: 'pending' | 'approved' | 'rejected';
    admin_remarks: string | null;
    reviewed_at: string | null;
    created_at: string;
    bank: string;
    receipt_url: string;
    receipt_is_pdf: boolean;
    booking: { id: number; reference: string } | null;
    agency: { id: number; name: string; city: string } | null;
    submitted_by: string | null;
};

// BookingPresenter::detail
export type BookingDetail = Omit<BookingRow, 'agency' | 'passengers'> & {
    adult_fare: number;
    child_fare: number;
    infant_fare: number;
    seats: number;
    hold_expires_at: string | null;
    confirmed_at: string | null;
    cancelled_at: string | null;
    cancel_reason: string | null;
    agent_remarks: string | null;
    booked_by: string;
    agency: {
        id: number;
        name: string;
        city: string;
        phone: string;
        email: string;
        contact_person: string;
    };
    group: GroupCard;
    passengers: Passenger[];
    payments: PaymentRow[];
    can: {
        edit_passengers: boolean;
        cancel: boolean;
        confirm: boolean;
        pay: boolean;
    };
};

export type BankAccount = {
    id: number;
    bank_name: string;
    account_title: string;
    account_number: string;
    iban: string | null;
    branch: string | null;
};

export type LedgerEntry = {
    id: number;
    voucher_number: string;
    entry_date: string;
    type: 'booking' | 'payment' | 'adjustment' | 'refund';
    description: string;
    debit: number;
    credit: number;
    balance: number;
    booking: { id: number; reference: string } | null;
};

export type Statement = {
    opening: number;
    closing: number;
    debit: number;
    credit: number;
    entries: LedgerEntry[];
};

// Shared on every page for agency logins.
export type SharedAgency = {
    id: number;
    name: string;
    status: 'pending' | 'active' | 'suspended';
    balance: number;
    // Signed up beyond the free plan: can look around but not act.
    locked: boolean;
    lockedMessage: string;
};

// For AMS staff: the free plan's agency limit (FreePlan::summary).
export type FreePlanSummary = {
    limit: number;
    agencies: number;
    locked: number;
    reached: boolean;
    contact: { name: string; whatsapp: string };
};

export type AdminQueue = {
    payments: number;
    agencies: number;
    holds: number;
};
