export function formatMoney(value: string | number): string {
    return new Intl.NumberFormat('en-BD', {
        style: 'currency', currency: 'BDT', minimumFractionDigits: 2,
    }).format(Number(value));
}

export function formatDate(value: string | null): string {
    if (!value) return 'Date unavailable';
    const [year, month, day] = value.split('-').map(Number);
    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC',
    }).format(new Date(Date.UTC(year, month - 1, day)));
}

export function formatMonth(value: string): string {
    const [year, month] = value.split('-').map(Number);
    return new Intl.DateTimeFormat('en-GB', {
        month: 'long', year: 'numeric', timeZone: 'UTC',
    }).format(new Date(Date.UTC(year, month - 1, 1)));
}
