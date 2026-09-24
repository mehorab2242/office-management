import { api } from './api';
import type { ApiResponse, MonthlyReport, ReportData } from '../types';
import type { ExpenseFilters } from './expenses';

export async function yearlyReport(year: number): Promise<ReportData> { const { data } = await api.get<ApiResponse<ReportData>>('/reports/yearly', { params: { year } }); return data.data; }
export async function customReport(filters: ExpenseFilters): Promise<ReportData> { const { data } = await api.get<ApiResponse<ReportData>>('/reports/custom', { params: filters }); return data.data; }
export async function monthlyReport(year: number, month: number): Promise<MonthlyReport> { const { data } = await api.get<ApiResponse<MonthlyReport>>('/reports/monthly', { params: { year, month } }); return data.data; }
export async function downloadExport(path: string, params: Record<string, unknown>, filename: string): Promise<void> { const response = await api.get(path, { params, responseType: 'blob' }); const url = URL.createObjectURL(response.data); const link = document.createElement('a'); link.href = url; link.download = filename; link.click(); URL.revokeObjectURL(url); }
