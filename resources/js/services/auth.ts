import { api } from './api';
import type { ApiResponse, User } from '../types';

interface LoginData {
    user: User;
    token: string;
}

export async function login(email: string, password: string): Promise<LoginData> {
    const response = await api.post<ApiResponse<LoginData>>('/login', { email, password });
    return response.data.data;
}

export async function fetchCurrentUser(): Promise<User> {
    const response = await api.get<ApiResponse<User>>('/me');
    return response.data.data;
}

export async function logout(): Promise<void> {
    await api.post('/logout');
}
