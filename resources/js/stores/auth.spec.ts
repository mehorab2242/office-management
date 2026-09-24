import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useAuthStore } from './auth';
import { tokenKey } from '../services/api';
import * as authService from '../services/auth';

vi.mock('../services/auth', () => ({
    login: vi.fn(), fetchCurrentUser: vi.fn(), logout: vi.fn(),
}));

const superAdmin = { id: 1, name: 'Super Admin', email: 'admin@example.com', role: 'super_admin' as const, is_active: true };

describe('authentication store', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('stores the token and authenticated user after login', async () => {
        vi.mocked(authService.login).mockResolvedValue({ user: superAdmin, token: 'valid-token' });
        const store = useAuthStore();
        await store.signIn(superAdmin.email, 'password');
        expect(store.user).toEqual(superAdmin);
        expect(store.isSuperAdmin).toBe(true);
        expect(sessionStorage.getItem(tokenKey)).toBe('valid-token');
    });

    it('clears an invalid stored session during initialization', async () => {
        sessionStorage.setItem(tokenKey, 'revoked-token');
        vi.mocked(authService.fetchCurrentUser).mockRejectedValue(new Error('Unauthorized'));
        const store = useAuthStore();
        await store.initialize();
        expect(store.initialized).toBe(true);
        expect(store.isAuthenticated).toBe(false);
        expect(sessionStorage.getItem(tokenKey)).toBeNull();
    });

    it('keeps the session empty when login fails', async () => {
        vi.mocked(authService.login).mockRejectedValue(new Error('Invalid credentials'));
        const store = useAuthStore();
        await expect(store.signIn('wrong@example.test', 'wrong')).rejects.toThrow('Invalid credentials');
        expect(store.isAuthenticated).toBe(false);
        expect(sessionStorage.getItem(tokenKey)).toBeNull();
    });
});
