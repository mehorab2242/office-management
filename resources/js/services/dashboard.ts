import { api } from './api';
import type { ApiResponse, DashboardData } from '../types';

export async function getDashboard(periodMonth?: string): Promise<DashboardData> {
    const [year, month] = (periodMonth ?? '').split('-');
    const response = await api.get<ApiResponse<DashboardData>>('/dashboard', {
        params: year && month ? { year: Number(year), month: Number(month) } : undefined,
    });
    return response.data.data;
}
