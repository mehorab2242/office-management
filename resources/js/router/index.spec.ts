import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { createAppRouter } from './index';
import { useAuthStore } from '../stores/auth';

describe('route protection', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('redirects guests to login with the requested path', async () => {
        const router = createAppRouter();
        await router.push('/expenses');
        expect(router.currentRoute.value.name).toBe('login');
        expect(router.currentRoute.value.query.redirect).toBe('/expenses');
    });

    it('rejects staff access to category administration', async () => {
        const auth = useAuthStore();
        auth.user = { id: 2, name: 'Staff', email: 'staff@example.com', role: 'staff', is_active: true };
        const router = createAppRouter();
        await router.push('/categories');
        expect(router.currentRoute.value.name).toBe('forbidden');
    });

    it('rejects staff access to staff management while allowing super admins', async () => {
        const staff = useAuthStore();
        staff.user = { id: 2, name: 'Staff', email: 'staff@example.com', role: 'staff', is_active: true };
        const staffRouter = createAppRouter();
        await staffRouter.push('/users');
        expect(staffRouter.currentRoute.value.name).toBe('forbidden');

        const superAdmin = useAuthStore();
        superAdmin.user = { id: 1, name: 'Super Admin', email: 'admin@example.com', role: 'super_admin', is_active: true };
        const adminRouter = createAppRouter();
        await adminRouter.push('/users');
        expect(adminRouter.currentRoute.value.name).toBe('users');
    });
});
