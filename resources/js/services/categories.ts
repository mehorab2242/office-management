import { api } from './api';
import type { ApiResponse, Category } from '../types';

export interface CategoryPayload {
    name: string;
    description: string | null;
    is_active: boolean;
}

export async function listCategories(): Promise<Category[]> {
    const response = await api.get<ApiResponse<Category[]>>('/categories');
    return response.data.data;
}

export async function createCategory(payload: CategoryPayload): Promise<Category> {
    const response = await api.post<ApiResponse<Category>>('/categories', payload);
    return response.data.data;
}

export async function updateCategory(id: number, payload: CategoryPayload): Promise<Category> {
    const response = await api.put<ApiResponse<Category>>(`/categories/${id}`, payload);
    return response.data.data;
}

export async function deleteCategory(id: number): Promise<void> {
    await api.delete(`/categories/${id}`);
}
