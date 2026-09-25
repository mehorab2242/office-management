import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AuditLogPage from './AuditLogPage.vue';
import * as auditService from '../services/audit';
import * as userService from '../services/users';

vi.mock('../services/audit');
vi.mock('../services/users');
vi.mock('../components/layout/AppLayout.vue', () => ({ default: { template: '<div><slot /></div>' } }));
vi.mock('../components/ui/DatePicker.vue', () => ({
    default: {
        props: ['modelValue'],
        emits: ['update:modelValue'],
        template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
    },
}));

describe('AuditLogPage', () => {
    beforeEach(() => {
        vi.mocked(auditService.listAuditLogs).mockReset().mockResolvedValue({
            success: true,
            data: [],
            meta: { current_page: 1, last_page: 1, per_page: 20, total: 0 },
        });
        vi.mocked(userService.listUsers).mockReset().mockResolvedValue([
            { id: 2, name: 'Jane Staff', email: 'jane@example.com', role: 'staff', is_active: true },
        ]);
    });

    it('clears audit filters and reloads the first page', async () => {
        const wrapper = mount(AuditLogPage);
        await flushPromises();

        await wrapper.get('input[placeholder="expense.updated"]').setValue('user.created');
        await wrapper.get('select').setValue('2');
        await wrapper.findAll('select')[1].setValue('User');
        await wrapper.get('[aria-label="From date"]').setValue('2026-09-01');
        await wrapper.get('[aria-label="To date"]').setValue('2026-09-25');
        await wrapper.get('form').trigger('submit');
        await flushPromises();

        await wrapper.findAll('button').find((button) => button.text() === 'Clear')!.trigger('click');
        await flushPromises();

        expect(auditService.listAuditLogs).toHaveBeenLastCalledWith({ page: 1 });
        expect(wrapper.get('input[placeholder="expense.updated"]').element.value).toBe('');
        expect(wrapper.findAll('select').map((select) => (select.element as HTMLSelectElement).value)).toEqual(['', '']);
        expect(wrapper.get('[aria-label="From date"]').element.value).toBe('');
        expect(wrapper.get('[aria-label="To date"]').element.value).toBe('');
    });
});
