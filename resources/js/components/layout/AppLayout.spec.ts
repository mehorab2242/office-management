import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import AppLayout from './AppLayout.vue';
import { useAuthStore } from '../../stores/auth';

describe('AppLayout', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('shows category management to administrators', () => {
        const auth = useAuthStore();
        auth.user = { id: 1, name: 'Admin', email: 'admin@example.com', role: 'admin', is_active: true };
        expect(mount(AppLayout).text()).toContain('Categories');
    });

    it('hides category management from staff', () => {
        const auth = useAuthStore();
        auth.user = { id: 2, name: 'Staff', email: 'staff@example.com', role: 'staff', is_active: true };
        expect(mount(AppLayout).text()).not.toContain('Categories');
    });
});
