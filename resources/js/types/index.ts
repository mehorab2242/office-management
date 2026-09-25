export type UserRole = 'super_admin' | 'staff';

export interface User {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    is_active: boolean;
    created_at?: string;
}

export interface Category {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
}

export type PaymentStatus = 'paid' | 'unpaid' | 'pending';
export type PaymentMethod = string;

export interface PayerAllocation {
    id?: number;
    payer_name: string;
    amount: string;
}

export interface Expense {
    id: number;
    expense_date: string | null;
    description: string;
    amount: string;
    category: Category | null;
    payment_status: PaymentStatus | null;
    payment_method: PaymentMethod | null;
    reference: string | null;
    note: string | null;
    created_by: User | null;
    payer_allocations: PayerAllocation[];
    attachments?: Attachment[];
    created_at?: string;
    updated_at?: string;
}
export interface Earning { id: number; earning_date: string; source: string; description: string; amount: string; reference: string | null; note: string | null; created_by: User | null; }
export interface EarningPayload { earning_date: string; source: string; description: string; amount: number; reference: string | null; note: string | null; }

export interface Attachment { id: number; original_name: string; mime_type: string; file_size: number; created_at: string; }

export interface ExpensePayload {
    expense_date: string;
    description: string;
    amount: number;
    category_id: number | null;
    payment_method: string | null;
    reference: string | null;
    note: string | null;
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface ApiResponse<T> {
    success: boolean;
    message?: string;
    data: T;
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
    meta: PaginationMeta;
}

export interface ExpenseSummary {
    total_amount: string;
    transaction_count: number;
    average_amount?: string;
    largest_amount?: string;
}

export interface DashboardSummary {
    total_amount: string;
    transaction_count: number;
    average_amount: string;
    largest_amount: string;
    financial?: FinancialSummary;
}

export interface DashboardData extends DashboardSummary {
    financial?: FinancialSummary | null;
    current_month: DashboardSummary;
    today?: DashboardSummary;
    current_year?: DashboardSummary;
    monthly_trend?: MonthTotal[];
    current_month_categories?: MonthlyCategoryTotal[];
    selected_month?: string;
    selected_month_summary?: DashboardSummary;
    selected_month_categories?: MonthlyCategoryTotal[];
    recent_expenses?: Expense[];
}
export interface FinancialSummary { total_revenue: string; total_cost: string; net_profit: string; is_loss: boolean; transaction_count: number; }

export interface MonthlyCategoryTotal {
    name: string;
    total: string;
}

export interface MonthlyReport extends ExpenseSummary {
    period: string;
    financial: FinancialSummary;
    categories: MonthlyCategoryTotal[];
    expenses: Expense[];
    meta: PaginationMeta;
}

export interface MonthTotal { month: number; total: string; revenue?: string; cost?: string; net_profit?: string; transaction_count: number; }
export interface ReportData { summary: ExpenseSummary; financial: FinancialSummary; categories: MonthlyCategoryTotal[]; expenses?: Expense[]; meta?: PaginationMeta; months?: MonthTotal[]; year?: number; average_monthly_amount?: string; }
export interface AuditLog { id: number; actor_user_id: number | null; actor: Pick<User, 'id' | 'name'> | null; action: string; subject_type: string; subject_id: number; old_values: Record<string, unknown> | null; new_values: Record<string, unknown> | null; created_at: string; }
export interface ImportSheet { name: string; highest_row: number; header_row: number | null; suggested_mapping: Record<string, string>; importable: boolean; }
export interface ImportRow { id: number; sheet_name: string; row_number: number; values: Record<string, string | number | null>; errors: string[]; status: string; }

export type ValidationErrors = Record<string, string[]>;
