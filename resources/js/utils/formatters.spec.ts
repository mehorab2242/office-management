import { describe, expect, it } from 'vitest';
import { formatDate, formatDateTime, formatMoney, formatMonth } from './formatters';

describe('formatters', () => {
    it('keeps date-only values on their original calendar date', () => {
        expect(formatDate('2026-09-17')).toBe('17 Sept 2026');
    });

    it('formats reporting months and Bangladeshi taka amounts', () => {
        expect(formatMonth('2026-09')).toBe('September 2026');
        expect(formatMoney('1250.50')).toContain('1,250.50');
    });

    it('supports 12-hour formatting without changing the default timestamp format', () => {
        const timestamp = '2026-09-25T13:05:00.000Z';

        expect(formatDateTime(timestamp, true)).toBe('25 Sept 2026, 07:05 pm');
        expect(formatDateTime(timestamp)).toBe('25 Sept 2026, 19:05');
    });
});
