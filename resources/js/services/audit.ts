import { api } from './api';
import type { AuditLog, PaginatedResponse } from '../types';
export async function listAuditLogs(params: Record<string, unknown> = {}): Promise<PaginatedResponse<AuditLog>> { const { data } = await api.get<PaginatedResponse<AuditLog>>('/audit-logs', { params }); return data; }
