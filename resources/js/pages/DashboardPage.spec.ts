import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import DashboardPage from './DashboardPage.vue';
import { useAuthStore } from '../stores/auth';
import * as dashboardService from '../services/dashboard';

vi.mock('../services/dashboard');

describe('DashboardPage', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        useAuthStore().user = { id: 1, name: 'Admin', email: 'admin@example.com', role: 'admin', is_active: true };
        const summary = { total_amount: '1000.00', paid_amount: '700.00', unpaid_amount: '200.00', pending_amount: '100.00', unspecified_amount: '0.00', transaction_count: 3 };
        vi.mocked(dashboardService.getDashboard).mockResolvedValue({ ...summary, current_month: summary });
        vi.mocked(dashboardService.getMonthlyReport).mockResolvedValue({ ...summary, period_month: '2026-09-01', categories: [] });
    });

    it('loads totals and refreshes the report when the month changes', async () => {
        const wrapper = mount(DashboardPage);
        expect(wrapper.text()).toContain('Loading dashboard');
        await flushPromises();
        expect(wrapper.text()).toContain('1,000.00');
        await wrapper.get('#month').setValue('2026-08');
        await flushPromises();
        expect(dashboardService.getMonthlyReport).toHaveBeenLastCalledWith('2026-08');
    });
});
