import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import * as authService from '../services/auth';
import { setUnauthorizedHandler, tokenKey } from '../services/api';
import type { User } from '../types';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null);
    const initialized = ref(false);
    const isLoading = ref(false);
    const isAuthenticated = computed(() => user.value !== null);
    const isAdmin = computed(() => user.value?.role === 'admin');

    function clearSession(): void {
        sessionStorage.removeItem(tokenKey);
        user.value = null;
    }

    async function initialize(): Promise<void> {
        isLoading.value = true;
        const token = sessionStorage.getItem(tokenKey);
        if (!token) {
            initialized.value = true;
            isLoading.value = false;
            return;
        }
        try {
            user.value = await authService.fetchCurrentUser();
        } catch {
            clearSession();
        } finally {
            initialized.value = true;
            isLoading.value = false;
        }
    }

    async function signIn(email: string, password: string): Promise<void> {
        isLoading.value = true;
        try {
            const data = await authService.login(email, password);
            sessionStorage.setItem(tokenKey, data.token);
            user.value = data.user;
        } finally {
            isLoading.value = false;
        }
    }

    async function signOut(): Promise<void> {
        try {
            await authService.logout();
        } finally {
            clearSession();
        }
    }

    setUnauthorizedHandler(clearSession);

    async function fetchUser(): Promise<void> { user.value = await authService.fetchCurrentUser(); }

    return {
        user, initialized, isLoading, isAuthenticated, isAdmin, initialize,
        signIn, signOut, login: signIn, logout: signOut, fetchUser, clearSession,
    };
});
