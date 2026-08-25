/**
 * Money crosses the wire in minor units. Formatting is presentation, so it
 * happens here, once, at the edge — never in storage and never in the API.
 */
export function formatMoney(cents: number | null, currency: string): string {
    if (cents === null) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(cents / 100);
}

export function formatDate(date: string | null): string {
    if (date === null) {
        return '—';
    }

    return new Intl.DateTimeFormat(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));
}
