import { api } from './api';
import type { ApiResponse, Earning, EarningPayload, PaginatedResponse } from '../types';
export interface EarningFilters { page?: number; per_page?: number; search?: string; source?: string; date_from?: string; date_to?: string; }
export async function listEarnings(filters: EarningFilters): Promise<PaginatedResponse<Earning>> { const { data } = await api.get<PaginatedResponse<Earning>>('/earnings', { params: filters }); return data; }
export async function createEarning(payload: EarningPayload): Promise<Earning> { const { data } = await api.post<ApiResponse<Earning>>('/earnings', payload); return data.data; }
export async function updateEarning(id: number, payload: EarningPayload): Promise<Earning> { const { data } = await api.put<ApiResponse<Earning>>(`/earnings/${id}`, payload); return data.data; }
export async function deleteEarning(id: number): Promise<void> { await api.delete(`/earnings/${id}`); }
