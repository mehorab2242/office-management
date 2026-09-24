import { describe, expect, it } from 'vitest';
import { formatDate, formatMoney, formatMonth } from './formatters';

describe('formatters', () => {
    it('keeps date-only values on their original calendar date', () => {
        expect(formatDate('2026-09-17')).toBe('17 Sept 2026');
    });

    it('formats reporting months and Bangladeshi taka amounts', () => {
        expect(formatMonth('2026-09')).toBe('September 2026');
        expect(formatMoney('1250.50')).toContain('1,250.50');
    });
});
