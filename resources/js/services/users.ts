import { api } from './api';
import type { ApiResponse, PaginatedResponse, User } from '../types';
export async function listUsers(): Promise<User[]> { const { data } = await api.get<PaginatedResponse<User>>('/users'); return data.data; }
export async function createUser(payload: Record<string, unknown>): Promise<User> { const { data } = await api.post<ApiResponse<User>>('/users', payload); return data.data; }
export async function updateUser(id: number, payload: Record<string, unknown>): Promise<User> { const { data } = await api.put<ApiResponse<User>>(`/users/${id}`, payload); return data.data; }
