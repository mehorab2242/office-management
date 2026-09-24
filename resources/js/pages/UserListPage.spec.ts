import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import UserListPage from './UserListPage.vue';
import { useAuthStore } from '../stores/auth';
import * as usersService from '../services/users';
import type { User } from '../types';

vi.mock('../services/users');

const superAdmin: User = {
    id: 1, name: 'Super Admin', email: 'admin@example.com', role: 'super_admin', is_active: true,
    created_at: '2026-01-15T09:30:00+00:00',
};
const activeStaff: User = {
    id: 2, name: 'Jane Staff', email: 'jane@example.com', role: 'staff', is_active: true,
    created_at: '2026-09-01T10:00:00+00:00',
};
const inactiveStaff: User = { ...activeStaff, is_active: false };

function setInput(input: HTMLInputElement, value: string): void {
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

function dialogButton(label: string): HTMLButtonElement | undefined {
    return Array.from(document.body.querySelectorAll('button'))
        .find((button) => button.textContent?.includes(label));
}

describe('UserListPage', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        const auth = useAuthStore();
        auth.user = superAdmin;
        vi.mocked(usersService.listUsers).mockReset().mockResolvedValue([activeStaff]);
        vi.mocked(usersService.createUser).mockReset().mockResolvedValue(activeStaff);
        vi.mocked(usersService.updateUser).mockReset().mockResolvedValue(activeStaff);
    });

    it('renders staff accounts with role, status, and created date', async () => {
        const wrapper = mount(UserListPage);
        await flushPromises();

        expect(wrapper.text()).toContain('Jane Staff');
        expect(wrapper.text()).toContain('jane@example.com');
        expect(wrapper.text()).toContain('Staff');
        expect(wrapper.text()).toContain('Super Admin');
        expect(wrapper.text()).toContain('Active');
    });

    it('creates a staff account without sending a role', async () => {
        const wrapper = mount(UserListPage, { attachTo: document.body });
        await flushPromises();

        await wrapper.findAll('button').find((button) => button.text().includes('Add Staff'))!.trigger('click');
        await flushPromises();

        const [nameInput, emailInput, passwordInput, confirmationInput] =
            Array.from(document.body.querySelectorAll('input'));
        setInput(nameInput, 'New Staff');
        setInput(emailInput, 'new.staff@example.com');
        setInput(passwordInput, 'long-password-123');
        setInput(confirmationInput, 'long-password-123');
        await flushPromises();

        dialogButton('Create staff account')?.click();
        await flushPromises();

        expect(usersService.createUser).toHaveBeenCalledWith({
            name: 'New Staff',
            email: 'new.staff@example.com',
            password: 'long-password-123',
            password_confirmation: 'long-password-123',
            is_active: true,
        });
        wrapper.unmount();
    });

    it('confirms deactivation and reactivates staff accounts', async () => {
        vi.mocked(usersService.listUsers).mockResolvedValue([inactiveStaff]);
        vi.mocked(usersService.listUsers).mockResolvedValueOnce([activeStaff]);
        const wrapper = mount(UserListPage, { attachTo: document.body });
        await flushPromises();

        await wrapper.find('[aria-label="Deactivate Jane Staff"]').trigger('click');
        await flushPromises();
        expect(document.body.textContent).toContain('Deactivate Jane Staff?');

        dialogButton('Deactivate')?.click();
        await flushPromises();
        expect(usersService.updateUser).toHaveBeenCalledWith(2, { is_active: false });

        await wrapper.find('[aria-label="Activate Jane Staff"]').trigger('click');
        await flushPromises();
        expect(usersService.updateUser).toHaveBeenCalledWith(2, { is_active: true });
        wrapper.unmount();
    });
});
