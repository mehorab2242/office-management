import { api } from './api';
import type { ApiResponse, Expense, ExpensePayload, PaginatedResponse } from '../types';

export interface ExpenseFilters {
    page?: number;
    per_page?: number;
    search?: string;
    category_id?: number;
    payment_status?: string;
    payment_method?: string;
    date_from?: string;
    date_to?: string;
    amount_min?: number;
    amount_max?: number;
    sort?: string;
    direction?: 'asc' | 'desc';
}

export async function listExpenses(filters: ExpenseFilters): Promise<PaginatedResponse<Expense>> {
    const response = await api.get<PaginatedResponse<Expense>>('/expenses', { params: filters });
    return response.data;
}

export async function getExpense(id: number): Promise<Expense> {
    const response = await api.get<ApiResponse<Expense>>(`/expenses/${id}`);
    return response.data.data;
}

export async function createExpense(payload: ExpensePayload): Promise<Expense> {
    const response = await api.post<ApiResponse<Expense>>('/expenses', payload);
    return response.data.data;
}

export async function updateExpense(id: number, payload: ExpensePayload): Promise<Expense> {
    const response = await api.put<ApiResponse<Expense>>(`/expenses/${id}`, payload);
    return response.data.data;
}

export async function deleteExpense(id: number): Promise<void> {
    await api.delete(`/expenses/${id}`);
}
