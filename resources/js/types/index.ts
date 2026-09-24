export type UserRole = 'admin' | 'staff';

export interface User {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    is_active: boolean;
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
    period_month: string;
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

export interface Attachment { id: number; original_name: string; mime_type: string; file_size: number; created_at: string; }

export interface ExpensePayload {
    period_month: string;
    expense_date: string;
    description: string;
    amount: number;
    category_id: number | null;
    payment_status: PaymentStatus | null;
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
    paid_amount: string;
    unpaid_amount: string;
    pending_amount: string;
    unspecified_amount: string;
    transaction_count: number;
    average_amount?: string;
}

export interface DashboardData extends ExpenseSummary {
    current_month: ExpenseSummary;
    today?: ExpenseSummary;
    current_year?: ExpenseSummary;
    monthly_trend?: MonthTotal[];
    current_month_categories?: MonthlyCategoryTotal[];
    recent_expenses?: Expense[];
}

export interface MonthlyCategoryTotal {
    name: string;
    total: string;
}

export interface MonthlyReport extends ExpenseSummary {
    period_month: string;
    categories: MonthlyCategoryTotal[];
}

export interface MonthTotal { month: number; total: string; transaction_count: number; }
export interface ReportData { summary: ExpenseSummary; categories: MonthlyCategoryTotal[]; expenses?: Expense[]; meta?: PaginationMeta; months?: MonthTotal[]; year?: number; }
export interface AuditLog { id: number; actor_user_id: number | null; actor: Pick<User, 'id' | 'name'> | null; action: string; subject_type: string; subject_id: number; old_values: Record<string, unknown> | null; new_values: Record<string, unknown> | null; created_at: string; }
export interface ImportSheet { name: string; highest_row: number; header_row: number | null; suggested_mapping: Record<string, string>; importable: boolean; }
export interface ImportRow { id: number; sheet_name: string; row_number: number; values: Record<string, string | number | null>; errors: string[]; status: string; }

export type ValidationErrors = Record<string, string[]>;
