import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ExpenseListPage from './ExpenseListPage.vue';
import { useAuthStore } from '../stores/auth';
import * as expenseService from '../services/expenses';
import * as categoryService from '../services/categories';

vi.mock('../services/expenses');
vi.mock('../services/categories');

const expense = {
    id: 7, period_month: '2026-09-01', expense_date: '2026-09-17', description: 'Office internet',
    amount: '2500.00', category: { id: 3, name: 'Utilities', description: null, is_active: true },
    payment_status: 'paid' as const, payment_method: 'Bank', reference: null, note: null,
    created_by: null, payer_allocations: [],
};

describe('ExpenseListPage', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.mocked(categoryService.listCategories).mockResolvedValue([expense.category]);
        vi.mocked(expenseService.listExpenses).mockResolvedValue({
            success: true, data: [expense], meta: { current_page: 1, last_page: 1, per_page: 15, total: 1 },
        });
    });

    it('renders API expenses and hides deletion from staff', async () => {
        const auth = useAuthStore();
        auth.user = { id: 2, name: 'Staff', email: 'staff@example.com', role: 'staff', is_active: true };
        const wrapper = mount(ExpenseListPage);
        await flushPromises();
        expect(wrapper.text()).toContain('Office internet');
        expect(wrapper.text()).toContain('BDT');
        expect(wrapper.find('[aria-label="Delete expense"]').exists()).toBe(false);
        expect(wrapper.find('[aria-label="Edit expense"]').exists()).toBe(false);
    });

    it('shows deletion to administrators and passes category filters to the API', async () => {
        const auth = useAuthStore();
        auth.user = { id: 1, name: 'Super Admin', email: 'admin@example.com', role: 'super_admin', is_active: true };
        const wrapper = mount(ExpenseListPage);
        await flushPromises();
        expect(wrapper.find('[aria-label="Delete expense"]').exists()).toBe(true);
        await wrapper.get('[aria-label="Category"]').setValue('3');
        await flushPromises();
        expect(expenseService.listExpenses).toHaveBeenLastCalledWith(expect.objectContaining({ category_id: 3 }));
    });

    it('requires confirmation and refreshes after an administrator deletes an expense', async () => {
        const auth = useAuthStore();
        auth.user = { id: 1, name: 'Super Admin', email: 'admin@example.com', role: 'super_admin', is_active: true };
        vi.mocked(expenseService.deleteExpense).mockResolvedValue();
        const wrapper = mount(ExpenseListPage, { attachTo: document.body });
        await flushPromises();
        await wrapper.find('[aria-label="Delete expense"]').trigger('click');
        await flushPromises();
        expect(document.body.textContent).toContain('This action permanently removes the expense record.');
        const confirm = Array.from(document.body.querySelectorAll('button')).find((button) => button.textContent?.includes('Delete expense'));
        confirm?.click();
        await flushPromises();
        expect(expenseService.deleteExpense).toHaveBeenCalledWith(7);
        expect(expenseService.listExpenses).toHaveBeenCalledTimes(2);
        wrapper.unmount();
    });
});
