import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ExpenseForm from './ExpenseForm.vue';

const categories = [{ id: 3, name: 'Utilities', description: null, is_active: true }];

describe('ExpenseForm', () => {
    it('shows local errors rather than submitting an empty form', async () => {
        const wrapper = mount(ExpenseForm, { props: { categories } });
        await wrapper.find('form').trigger('submit');
        expect(wrapper.emitted('submit')).toBeUndefined();
        expect(wrapper.text()).toContain('Choose an expense date.');
        expect(wrapper.text()).toContain('Enter a description.');
    });

    it('emits the API payload with a first-of-month period', async () => {
        const wrapper = mount(ExpenseForm, { props: { categories } });
        await wrapper.get('#expense-date').setValue('2026-09-17');
        await wrapper.get('#description').setValue('Internet service');
        await wrapper.get('#amount').setValue('2500.50');
        await wrapper.get('#category').setValue('3');
        await wrapper.find('form').trigger('submit');
        expect(wrapper.emitted('submit')?.[0]?.[0]).toMatchObject({
            period_month: '2026-09-01', expense_date: '2026-09-17',
            description: 'Internet service', amount: 2500.5, category_id: 3,
        });
    });
});
