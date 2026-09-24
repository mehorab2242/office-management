import { api } from './api';
import type { ApiResponse, Attachment } from '../types';
export async function uploadAttachment(expenseId: number, file: File): Promise<Attachment> { const form = new FormData(); form.append('file', file); const { data } = await api.post<ApiResponse<Attachment>>(`/expenses/${expenseId}/attachments`, form); return data.data; }
export async function downloadAttachment(expenseId: number, attachment: Attachment): Promise<void> { const response = await api.get(`/expenses/${expenseId}/attachments/${attachment.id}`, { responseType: 'blob' }); const url = URL.createObjectURL(response.data); const link = document.createElement('a'); link.href = url; link.download = attachment.original_name; link.click(); URL.revokeObjectURL(url); }
export async function deleteAttachment(expenseId: number, attachmentId: number): Promise<void> { await api.delete(`/expenses/${expenseId}/attachments/${attachmentId}`); }
