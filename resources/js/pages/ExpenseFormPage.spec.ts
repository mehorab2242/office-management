import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ExpenseFormPage from './ExpenseFormPage.vue';
import * as categoryService from '../services/categories';
import * as expenseService from '../services/expenses';

const routerState = vi.hoisted(() => ({ params: {} as Record<string, string> }));
const push = vi.hoisted(() => vi.fn());

vi.mock('vue-router', async (importOriginal) => ({
    ...await importOriginal<typeof import('vue-router')>(),
    useRoute: () => routerState,
    useRouter: () => ({ push }),
}));
vi.mock('../services/categories');
vi.mock('../services/expenses');

const category = { id: 3, name: 'Utilities', description: null, is_active: true };
const expense = {
    id: 7, period_month: '2026-09-01', expense_date: '2026-09-17', description: 'Office internet',
    amount: '2500.00', category, payment_status: 'paid' as const, payment_method: 'Bank',
    reference: null, note: null, created_by: null, payer_allocations: [],
};

describe('ExpenseFormPage', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        routerState.params = {};
        vi.mocked(categoryService.listCategories).mockResolvedValue([category]);
    });

    it('creates an expense and returns to the list', async () => {
        vi.mocked(expenseService.createExpense).mockResolvedValue(expense);
        const wrapper = mount(ExpenseFormPage);
        await flushPromises();
        await wrapper.get('#expense-date').setValue('2026-09-17');
        await wrapper.get('#description').setValue('Office internet');
        await wrapper.get('#amount').setValue('2500');
        await wrapper.find('form').trigger('submit');
        await flushPromises();
        expect(expenseService.createExpense).toHaveBeenCalledWith(expect.objectContaining({ description: 'Office internet', amount: 2500 }));
        expect(push).toHaveBeenCalledWith({ name: 'expenses' });
    });

    it('loads and updates an existing expense', async () => {
        routerState.params = { id: '7' };
        vi.mocked(expenseService.getExpense).mockResolvedValue(expense);
        vi.mocked(expenseService.updateExpense).mockResolvedValue({ ...expense, description: 'Updated internet' });
        const wrapper = mount(ExpenseFormPage);
        await flushPromises();
        expect(wrapper.get('#description').element).toHaveProperty('value', 'Office internet');
        await wrapper.get('#description').setValue('Updated internet');
        await wrapper.find('form').trigger('submit');
        await flushPromises();
        expect(expenseService.updateExpense).toHaveBeenCalledWith(7, expect.objectContaining({ description: 'Updated internet' }));
    });
});
