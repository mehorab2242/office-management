import { api } from './api';
import type { ApiResponse, OnHandReceipt, OnHandReceiptPayload, OnHandSummary, PaginatedResponse } from '../types';
export interface OnHandFilters { page?: number; per_page?: number; date_from?: string; date_to?: string; }
export interface OnHandResponse extends PaginatedResponse<OnHandReceipt> { summary: OnHandSummary; }
export async function listOnHandReceipts(filters: OnHandFilters): Promise<OnHandResponse> { const { data } = await api.get<OnHandResponse>('/on-hand-receipts', { params: filters }); return data; }
export async function createOnHandReceipt(payload: OnHandReceiptPayload): Promise<OnHandReceipt> { const { data } = await api.post<ApiResponse<OnHandReceipt>>('/on-hand-receipts', payload); return data.data; }
export async function updateOnHandReceipt(id: number, payload: OnHandReceiptPayload): Promise<OnHandReceipt> { const { data } = await api.put<ApiResponse<OnHandReceipt>>(`/on-hand-receipts/${id}`, payload); return data.data; }
export async function deleteOnHandReceipt(id: number): Promise<void> { await api.delete(`/on-hand-receipts/${id}`); }
