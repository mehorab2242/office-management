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
});
