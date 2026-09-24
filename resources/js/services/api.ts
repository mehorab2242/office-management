import axios, { AxiosError } from 'axios';
import type { ValidationErrors } from '../types';

export const tokenKey = 'office_management_token';

export const api = axios.create({
    baseURL: '/api',
    headers: { Accept: 'application/json' },
});

let unauthorizedHandler: (() => void) | null = null;

export function setUnauthorizedHandler(handler: () => void): void {
    unauthorizedHandler = handler;
}

api.interceptors.request.use((config) => {
    const token = sessionStorage.getItem(tokenKey);
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

api.interceptors.response.use(undefined, (error: AxiosError) => {
    if (error.response?.status === 401) {
        sessionStorage.removeItem(tokenKey);
        unauthorizedHandler?.();
    }
    return Promise.reject(error);
});

interface ErrorPayload {
    message?: string;
    errors?: ValidationErrors;
}

export function apiErrorMessage(error: unknown, fallback = 'Something went wrong. Please try again.'): string {
    if (axios.isAxiosError<ErrorPayload>(error)) {
        return error.response?.data.message ?? fallback;
    }
    return fallback;
}

export function validationErrors(error: unknown): ValidationErrors {
    if (axios.isAxiosError<ErrorPayload>(error)) {
        return error.response?.data.errors ?? {};
    }
    return {};
}
