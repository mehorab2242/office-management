import { api } from './api';
import type { ApiResponse, DashboardData, MonthlyReport } from '../types';

export async function getDashboard(): Promise<DashboardData> {
    const response = await api.get<ApiResponse<DashboardData>>('/dashboard');
    return response.data.data;
}

export async function getMonthlyReport(periodMonth: string): Promise<MonthlyReport> {
    const [year, month] = periodMonth.split('-');
    const response = await api.get<ApiResponse<MonthlyReport>>('/reports/monthly', {
        params: { year: Number(year), month: Number(month) },
    });
    return response.data.data;
}
