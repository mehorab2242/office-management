import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import AppLayout from './AppLayout.vue';
import { useAuthStore } from '../../stores/auth';

describe('AppLayout', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('shows category and staff management navigation to super administrators', () => {
        const auth = useAuthStore();
        auth.user = { id: 1, name: 'Super Admin', email: 'admin@example.com', role: 'super_admin', is_active: true };
        const text = mount(AppLayout).text();
        expect(text).toContain('Categories');
        expect(text).toContain('Staff Management');
    });

    it('hides category and staff management navigation from staff', () => {
        const auth = useAuthStore();
        auth.user = { id: 2, name: 'Staff', email: 'staff@example.com', role: 'staff', is_active: true };
        const text = mount(AppLayout).text();
        expect(text).not.toContain('Categories');
        expect(text).not.toContain('Staff Management');
    });
});
